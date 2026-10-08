<?php

namespace App\Services;

use App\Enums\SignalType;
use App\Models\IntegritySignal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Signaux d'intégrité (CDC section 7). Chaque détecteur est inactif tant que son seuil
 * n'est pas configuré. Le service n'écrit que dans `integrity_signals` : jamais d'action
 * sur un contenu ni sur un compte. Les cibles sont des identifiants internes.
 */
class IntegrityScanner
{
    /**
     * Analyse la fenêtre des `window_hours` dernières heures se terminant à `$until`.
     *
     * @return Collection<int, IntegritySignal>
     */
    public function scan(?Carbon $until = null): Collection
    {
        $until ??= now();
        $since = $until->copy()->subHours((int) config('votalis.integrity.window_hours', 24));
        $date = $until->toDateString();

        $found = collect([
            ...$this->registrationSpike($since, $until),
            ...$this->voteSpikes($since, $until),
            ...$this->identicalVoting($since, $until),
            ...$this->nearDuplicateContent($since, $until),
            ...$this->atypicalHours($since, $until),
        ]);

        // Un signal par type, jour et cibles : un second passage le même jour ne duplique rien.
        return $found->map(function (array $signal) use ($date): IntegritySignal {
            $existing = IntegritySignal::query()
                ->where('type', $signal['type']->value)
                ->whereDate('window_date', $date)
                ->whereRaw('targets = ?::jsonb', [json_encode($signal['targets'], JSON_THROW_ON_ERROR)])
                ->first();

            return $existing ?? IntegritySignal::create([
                'type' => $signal['type'],
                'window_date' => $date,
                'targets' => $signal['targets'],
                'severity' => $signal['severity'],
                'details' => $signal['details'],
            ]);
        })->values();
    }

    /**
     * @return list<array{type: SignalType, severity: int, targets: array<string, mixed>, details: array<string, mixed>}>
     */
    private function registrationSpike(Carbon $since, Carbon $until): array
    {
        $threshold = config('votalis.integrity.registration_spike');

        if (! is_int($threshold)) {
            return [];
        }

        $count = DB::table('users')->whereBetween('created_at', [$since, $until])->count();

        if ($count < $threshold) {
            return [];
        }

        return [[
            'type' => SignalType::RegistrationSpike,
            'severity' => $count >= $threshold * 3 ? 3 : 2,
            'targets' => ['scope' => 'registrations'],
            'details' => ['count' => $count, 'threshold' => $threshold],
        ]];
    }

    /**
     * @return list<array{type: SignalType, severity: int, targets: array<string, mixed>, details: array<string, mixed>}>
     */
    private function voteSpikes(Carbon $since, Carbon $until): array
    {
        $threshold = config('votalis.integrity.vote_spike');

        if (! is_int($threshold)) {
            return [];
        }

        return array_values(DB::table('votes')
            ->select('proposal_id')
            ->selectRaw('COUNT(*) AS n')
            ->whereBetween('created_at', [$since, $until])
            ->groupBy('proposal_id')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->get()
            ->map(fn (object $row) => [
                'type' => SignalType::VoteSpike,
                'severity' => (int) $row->n >= $threshold * 3 ? 3 : 2,
                'targets' => ['proposal_id' => (int) $row->proposal_id],
                'details' => ['count' => (int) $row->n, 'threshold' => $threshold],
            ])
            ->all());
    }

    /**
     * Comptes récents partageant au moins N votes strictement identiques (mêmes réponses aux deux questions).
     *
     * @return list<array{type: SignalType, severity: int, targets: array<string, mixed>, details: array<string, mixed>}>
     */
    private function identicalVoting(Carbon $since, Carbon $until): array
    {
        $minShared = config('votalis.integrity.identical_voting_min_shared');

        if (! is_int($minShared)) {
            return [];
        }

        $minAccounts = (int) config('votalis.integrity.identical_voting_min_accounts', 2);
        $accountSince = $until->copy()->subDays((int) config('votalis.integrity.identical_voting_account_days', 30));

        // Paires de comptes récents ayant voté identiquement sur au moins `minShared` propositions dans la fenêtre.
        $pairs = DB::table('votes as a')
            ->join('votes as b', function ($join): void {
                $join->on('a.proposal_id', '=', 'b.proposal_id')
                    ->on('a.desirable', '=', 'b.desirable')
                    ->on('a.necessary', '=', 'b.necessary')
                    ->whereColumn('a.participant_id', '<', 'b.participant_id');
            })
            ->join('users as ua', 'ua.id', '=', 'a.participant_id')
            ->join('users as ub', 'ub.id', '=', 'b.participant_id')
            ->where('ua.created_at', '>=', $accountSince)
            ->where('ub.created_at', '>=', $accountSince)
            ->whereBetween('a.created_at', [$since, $until])
            ->whereBetween('b.created_at', [$since, $until])
            ->select('a.participant_id as x', 'b.participant_id as y')
            ->selectRaw('COUNT(*) AS shared')
            ->groupBy('a.participant_id', 'b.participant_id')
            ->havingRaw('COUNT(*) >= ?', [$minShared])
            ->get();

        if ($pairs->isEmpty()) {
            return [];
        }

        // Regroupement des paires en composantes connexes : un signal par groupe de comptes.
        $groups = [];
        foreach ($pairs as $pair) {
            $x = (int) $pair->x;
            $y = (int) $pair->y;
            $found = null;
            foreach ($groups as $i => $members) {
                if (in_array($x, $members, true) || in_array($y, $members, true)) {
                    $found = $i;
                    break;
                }
            }
            if ($found === null) {
                $groups[] = [$x, $y];
            } else {
                $groups[$found] = array_values(array_unique([...$groups[$found], $x, $y]));
            }
        }

        $signals = [];
        foreach ($groups as $members) {
            if (count($members) < $minAccounts) {
                continue;
            }
            sort($members);
            $signals[] = [
                'type' => SignalType::IdenticalVoting,
                'severity' => count($members) >= 5 ? 3 : 2,
                'targets' => ['user_ids' => $members],
                'details' => ['accounts' => count($members), 'min_shared' => $minShared],
            ];
        }

        return $signals;
    }

    /**
     * Propositions déposées dans la fenêtre, presque identiques à une autre d'un auteur différent (embeddings pgvector).
     *
     * @return list<array{type: SignalType, severity: int, targets: array<string, mixed>, details: array<string, mixed>}>
     */
    private function nearDuplicateContent(Carbon $since, Carbon $until): array
    {
        $threshold = config('votalis.integrity.duplicate_content_similarity');

        if (! is_float($threshold) && ! is_int($threshold)) {
            return [];
        }

        $rows = DB::table('proposals as a')
            ->join('proposals as b', function ($join): void {
                $join->whereColumn('b.id', '<>', 'a.id')->whereColumn('b.author_id', '<>', 'a.author_id');
            })
            ->whereBetween('a.created_at', [$since, $until])
            ->whereNotNull('a.embedding')->whereNotNull('b.embedding')
            ->whereNotNull('a.author_id')->whereNotNull('b.author_id')
            ->whereRaw('1 - (a.embedding <=> b.embedding) >= ?', [(float) $threshold])
            ->select('a.id as x', 'b.id as y')
            ->selectRaw('1 - (a.embedding <=> b.embedding) AS similarity')
            ->get();

        return array_values($rows->map(fn (object $row) => [
            'type' => SignalType::NearDuplicateContent,
            'severity' => 2,
            'targets' => ['proposal_ids' => [min((int) $row->x, (int) $row->y), max((int) $row->x, (int) $row->y)]],
            'details' => ['similarity' => round((float) $row->similarity, 3)],
        ])->unique(fn (array $s) => implode('-', $s['targets']['proposal_ids']))->all());
    }

    /**
     * Part des votes de la fenêtre émis la nuit (heure du serveur), à partir d'un volume minimal.
     *
     * @return list<array{type: SignalType, severity: int, targets: array<string, mixed>, details: array<string, mixed>}>
     */
    private function atypicalHours(Carbon $since, Carbon $until): array
    {
        $share = config('votalis.integrity.night_share');

        if (! is_float($share) && ! is_int($share)) {
            return [];
        }

        $minVotes = (int) config('votalis.integrity.night_min_votes', 50);
        $start = (int) config('votalis.integrity.night_start', 2);
        $end = (int) config('votalis.integrity.night_end', 6);

        $total = DB::table('votes')->whereBetween('created_at', [$since, $until])->count();

        if ($total < $minVotes) {
            return [];
        }

        $night = DB::table('votes')
            ->whereBetween('created_at', [$since, $until])
            ->whereRaw('EXTRACT(HOUR FROM created_at) >= ? AND EXTRACT(HOUR FROM created_at) < ?', [$start, $end])
            ->count();

        if ($night / $total < (float) $share) {
            return [];
        }

        return [[
            'type' => SignalType::AtypicalHours,
            'severity' => 1,
            'targets' => ['scope' => 'votes'],
            'details' => ['night_votes' => $night, 'total_votes' => $total, 'share' => round($night / $total, 3)],
        ]];
    }
}
