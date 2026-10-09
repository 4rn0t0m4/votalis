<?php

namespace App\Services;

use App\Enums\ConsensusRunStatus;
use App\Models\ConsensusRun;
use App\Models\ConsensusScore;
use App\Models\Proposal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Random\Randomizer;
use RuntimeException;

/**
 * Classement par consensus (lot 7, CDC section 5 ; algorithme public dans docs/classement.md).
 *
 * Laravel prépare la matrice des votes « souhaitable » et « nécessaire », remplace chaque compte
 * par un rang tiré au hasard à chaque calcul, l'envoie au service interne et enregistre les
 * scores. Le score n'est jamais calculé ici. Désactivé par défaut (`CONSENSUS_ENABLED`).
 */
class Consensus
{
    public function enabled(): bool
    {
        return (bool) config('votalis.consensus.enabled', false);
    }

    /** Dernier calcul réussi encore affichable (activé, non périmé), sinon null. */
    public function activeRun(): ?ConsensusRun
    {
        if (! $this->enabled()) {
            return null;
        }

        $maxAge = (int) config('votalis.consensus.max_age_hours', 24);

        return ConsensusRun::query()
            ->where('status', ConsensusRunStatus::Computed)
            ->where('computed_at', '>=', now()->subHours($maxAge))
            ->latest('computed_at')
            ->latest('id')
            ->first();
    }

    public function latestRun(): ?ConsensusRun
    {
        return ConsensusRun::query()->latest('computed_at')->latest('id')->first();
    }

    public function scoreFor(Proposal $proposal): ?ConsensusScore
    {
        $run = $this->activeRun();

        return $run === null ? null : ConsensusScore::query()
            ->where('run_id', $run->id)
            ->where('proposal_id', $proposal->id)
            ->first();
    }

    /** Explication affichée tant que l'onglet « Les plus consensuelles » ne peut pas être servi. */
    public function unavailableReason(): ?string
    {
        if (! $this->enabled()) {
            return 'Le classement par consensus n’est pas encore activé. Il le sera une fois quelques centaines de votants actifs atteints : en dessous, les familles de votants ne seraient pas significatives.';
        }

        if ($this->activeRun() === null) {
            return sprintf(
                'Pas encore assez de participation pour un classement par consensus : il faut au moins %d participants ayant voté %d fois et %d propositions publiées.',
                (int) config('votalis.consensus.min_participants'),
                (int) config('votalis.consensus.min_votes_per_participant'),
                (int) config('votalis.consensus.min_proposals'),
            );
        }

        return null;
    }

    /** @return array<string, int|float> */
    public function params(): array
    {
        $c = (array) config('votalis.consensus');

        return [
            'min_votes_per_participant' => (int) $c['min_votes_per_participant'],
            'min_voters_per_group' => (int) $c['min_voters_per_group'],
            'k_min' => (int) $c['k_min'],
            'k_max' => (int) $c['k_max'],
            'alpha' => (float) $c['alpha'],
            'seed' => (int) $c['seed'],
        ];
    }

    /**
     * Charge envoyée au service : propositions publiées et votes des participants retenus,
     * chaque compte remplacé par un rang aléatoire. Aucun identifiant de compte n'en sort.
     *
     * @return array{proposals: list<int>, votes: list<array{0: int, 1: int, 2: int, 3: int}>, params: array<string, int|float>, participants: int}
     */
    public function payload(): array
    {
        $params = $this->params();
        /** @var list<int> $proposals */
        $proposals = array_values(array_map('intval', Proposal::query()->published()->orderBy('id')->pluck('id')->all()));

        /** @var list<int> $retained */
        $retained = array_values(array_map('intval', DB::table('votes')
            ->whereIn('proposal_id', $proposals)
            ->groupBy('participant_id')
            ->havingRaw('count(*) >= ?', [$params['min_votes_per_participant']])
            ->pluck('participant_id')
            ->all()));

        // Rangs tirés au hasard (générateur cryptographique) à chaque calcul : impossible de
        // relier deux calculs ou un export à un compte.
        $shuffled = (new Randomizer)->shuffleArray($retained);
        $rank = array_flip($shuffled);

        $votes = [];
        DB::table('votes')
            ->whereIn('proposal_id', $proposals)
            ->whereIn('participant_id', $retained)
            ->select(['participant_id', 'proposal_id', 'desirable', 'necessary'])
            ->cursor()
            ->each(function (object $row) use (&$votes, $rank): void {
                /** @var object{participant_id: int, proposal_id: int, desirable: int, necessary: int} $row */
                $votes[] = [$rank[(int) $row->participant_id], (int) $row->proposal_id, (int) $row->desirable, (int) $row->necessary];
            });

        // Ordre des votes sans lien avec l'ordre des comptes.
        usort($votes, fn (array $a, array $b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);

        return ['proposals' => $proposals, 'votes' => $votes, 'params' => $params, 'participants' => count($retained)];
    }

    /**
     * Lance un calcul et l'enregistre. Sous les seuils, enregistre un calcul inactif sans appeler
     * le service. En cas d'échec, les scores du calcul précédent restent servis.
     *
     * @param  string|null  $exportPath  si fourni, la charge exacte envoyée y est écrite (audit)
     */
    public function run(?string $exportPath = null): ConsensusRun
    {
        $payload = $this->payload();
        $params = $payload['params'];
        $participants = $payload['participants'];
        $proposals = count($payload['proposals']);

        $base = [
            'participants' => $participants,
            'proposals' => $proposals,
            'params' => $params + [
                'min_participants' => (int) config('votalis.consensus.min_participants'),
                'min_proposals' => (int) config('votalis.consensus.min_proposals'),
            ],
            'computed_at' => now(),
        ];

        if ($participants < (int) config('votalis.consensus.min_participants') || $proposals < (int) config('votalis.consensus.min_proposals')) {
            return ConsensusRun::create($base + ['status' => ConsensusRunStatus::Inactive]);
        }

        $body = json_encode(['proposals' => $payload['proposals'], 'votes' => $payload['votes'], 'params' => $params], JSON_THROW_ON_ERROR);
        $digest = hash('sha256', $body);

        if ($exportPath !== null) {
            file_put_contents($exportPath, $body);
        }

        try {
            $response = Http::timeout((float) config('votalis.consensus.timeout', 120.0))
                ->acceptJson()
                ->withBody($body, 'application/json')
                ->post(EmbeddingClient::serviceUrl().'/consensus')
                ->throw();
            /** @var array<string, mixed> $result */
            $result = (array) $response->json();
            $this->validate($result, $payload['proposals']);
        } catch (ConnectionException|RequestException|RuntimeException $e) {
            Log::warning('Calcul de consensus en échec.', ['erreur' => mb_substr($e->getMessage(), 0, 300)]);

            return ConsensusRun::create($base + [
                'status' => ConsensusRunStatus::Failed,
                'input_digest' => $digest,
                'error' => mb_substr($e instanceof ConnectionException ? 'Service de calcul indisponible.' : $e->getMessage(), 0, 200),
            ]);
        }

        if ($result['status'] !== 'computed') {
            return ConsensusRun::create($base + [
                'status' => ConsensusRunStatus::Insufficient,
                'algo_version' => (string) $result['algo_version'],
                'participants' => (int) $result['participants'],
                'input_digest' => $digest,
            ]);
        }

        $run = DB::transaction(function () use ($base, $result, $digest): ConsensusRun {
            $run = ConsensusRun::create($base + [
                'status' => ConsensusRunStatus::Computed,
                'algo_version' => (string) $result['algo_version'],
                'participants' => (int) $result['participants'],
                'k' => (int) $result['k'],
                'silhouette' => $result['silhouette'],
                'groups' => $result['groups'],
                'input_digest' => $digest,
            ]);

            /** @var list<array{proposal: int, score: float|null, divisiveness: float|null, groups: list<array<string, mixed>>}> $scores */
            $scores = $result['proposals'];
            foreach (array_chunk($scores, 500) as $chunk) {
                DB::table('consensus_scores')->insert(array_map(fn (array $p) => [
                    'run_id' => $run->id,
                    'proposal_id' => $p['proposal'],
                    'score' => $p['score'],
                    'divisiveness' => $p['divisiveness'],
                    'groups' => json_encode($p['groups'], JSON_THROW_ON_ERROR),
                ], $chunk));
            }

            return $run;
        });

        $this->purge();

        return $run;
    }

    /** Ne garde que les scores des derniers calculs réussis. */
    private function purge(): void
    {
        $keep = ConsensusRun::query()
            ->where('status', ConsensusRunStatus::Computed)
            ->latest('computed_at')->latest('id')
            ->limit(max(1, (int) config('votalis.consensus.keep_runs', 10)))
            ->pluck('id');

        DB::table('consensus_scores')->whereNotIn('run_id', $keep)->delete();
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  list<int>  $proposals
     */
    private function validate(array $result, array $proposals): void
    {
        if (! in_array($result['status'] ?? null, ['computed', 'insufficient'], true) || ! is_string($result['algo_version'] ?? null)) {
            throw new RuntimeException('Réponse du service de calcul invalide.');
        }

        $returned = array_map(fn ($p) => is_array($p) ? ($p['proposal'] ?? null) : null, (array) ($result['proposals'] ?? []));
        sort($returned);

        if ($returned !== $proposals) {
            throw new RuntimeException('Réponse du service de calcul incohérente : propositions inattendues.');
        }

        foreach ((array) $result['proposals'] as $p) {
            foreach (['score', 'divisiveness'] as $key) {
                $value = $p[$key] ?? null;
                if ($value !== null && (! is_numeric($value) || $value < 0 || $value > 1)) {
                    throw new RuntimeException('Réponse du service de calcul invalide : score hors de [0, 1].');
                }
            }
        }
    }
}
