<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Classements multiples par thème (CDC 4.10). Aucun classement unique par soutien.
 * Définitions publiées dans docs/classement.md.
 */
class Rankings
{
    public const TABS = ['recentes', 'debattues', 'clivantes', 'necessaires', 'progression', 'arbitrages', 'consensuelles'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'recentes' => 'Récentes',
            'debattues' => 'Les plus débattues',
            'clivantes' => 'Les plus clivantes',
            'necessaires' => 'Nécessaires mais pas souhaitées',
            'progression' => 'Soutien en hausse après les arguments',
            'arbitrages' => 'Les plus choisies dans les arbitrages',
            'consensuelles' => 'Les plus consensuelles',
        ];
    }

    public function __construct(private readonly Consensus $consensus) {}

    /** Explication affichée à la place d'un onglet qui ne peut pas encore être servi. */
    public function pending(string $tab): ?string
    {
        return $tab === 'consensuelles' ? $this->consensus->unavailableReason() : null;
    }

    /**
     * @return Collection<int, Proposal> Propositions avec un attribut `metric` (texte affiché)
     */
    public function forTheme(Theme $theme, string $tab): Collection
    {
        $themeIds = array_values(array_map('intval', $theme->children()->pluck('id')->push($theme->id)->all()));
        // Les onglets fondés sur le consensus changent à chaque calcul : le numéro de calcul entre dans la clé.
        $run = in_array($tab, ['consensuelles', 'clivantes'], true) ? $this->consensus->activeRun() : null;
        $key = "rankings:{$theme->id}:{$tab}".($run ? ":run{$run->id}" : '');

        // Le cache ne contient que des scalaires (identifiants et libellés) : les modèles sont
        // rechargés à la lecture, jamais sérialisés (`cache.serializable_classes` est à false).
        /** @var list<array{id: int, metric: string|null}> $rows */
        $rows = Cache::remember($key, now()->addSeconds((int) config('votalis.rankings.cache_seconds', 300)), fn () => $this->compute($themeIds, $tab, $run?->id)
            ->map(fn (Proposal $p) => ['id' => $p->id, 'metric' => $p->getAttribute('metric')])
            ->values()
            ->all());

        $proposals = Proposal::query()->published()->with(['theme', 'author'])->findMany(array_column($rows, 'id'))->keyBy('id');

        /** @var Collection<int, Proposal> $ordered */
        $ordered = collect($rows)
            ->map(function (array $row) use ($proposals): ?Proposal {
                $proposal = $proposals->get($row['id']);

                return $proposal?->setAttribute('metric', $row['metric']);
            })
            ->filter()
            ->values();

        return $ordered;
    }

    /**
     * @param  list<int>  $themeIds
     * @return Collection<int, Proposal>
     */
    private function compute(array $themeIds, string $tab, ?int $runId = null): Collection
    {
        $limit = (int) config('votalis.rankings.per_tab', 20);
        $minVotes = (int) config('votalis.rankings.min_votes', 10);

        $base = Proposal::query()->published()->whereIn('theme_id', $themeIds)->with(['theme', 'author']);

        return match ($tab) {
            'debattues' => $base
                ->withCount(['arguments' => fn ($q) => $q->published()])
                ->orderByRaw('(votes_count + (select count(*) from arguments a where a.proposal_id = proposals.id and a.status = \'published\')) desc')
                ->orderByDesc('created_at')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', trans_choice(':count vote|:count votes', $p->votes_count).' · '.trans_choice(':count argument|:count arguments', (int) $p->getAttribute('arguments_count')))),

            // Calcul de consensus actif : écart entre le groupe le plus et le moins favorable (CDC 5).
            'clivantes' => $runId !== null ? $this->byConsensus($base, $runId, 'divisiveness')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', sprintf('écart de %d points entre groupes de votants', (int) round(100 * (float) $p->getAttribute('consensus_divisiveness')))))
                // Sinon : équilibre entre oui et non sur l'ensemble des votants.
                : $this->withVoteStats($base, $minVotes)
                    ->whereRaw('(vs.yes + vs.no) > 0')
                    ->orderByRaw('(1.0 - abs(vs.yes - vs.no)::numeric / nullif(vs.yes + vs.no, 0)) desc, vs.total desc')
                    ->limit($limit)->get()
                    ->each(fn (Proposal $p) => $p->setAttribute('metric', VoteService::percent((int) $p->getAttribute('yes'), (int) $p->getAttribute('total')).' % oui · '.VoteService::percent((int) $p->getAttribute('no'), (int) $p->getAttribute('total')).' % non')),

            'necessaires' => $this->withVoteStats($base, $minVotes)
                ->whereRaw('vs.nec_yes > vs.yes')
                ->orderByRaw('(vs.nec_yes - vs.yes)::numeric / vs.total desc, vs.total desc')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', VoteService::percent((int) $p->getAttribute('nec_yes'), (int) $p->getAttribute('total')).' % nécessaire · '.VoteService::percent((int) $p->getAttribute('yes'), (int) $p->getAttribute('total')).' % souhaitable')),

            'progression' => $base
                ->joinSub(
                    Vote::query()
                        ->selectRaw('proposal_id, count(*) as revised, sum(case when desirable_initial <> 1 and desirable = 1 then 1 else 0 end) as gained')
                        ->where('revised_after_arguments', true)
                        ->groupBy('proposal_id'),
                    'rv', 'rv.proposal_id', '=', 'proposals.id'
                )
                ->select('proposals.*', 'rv.revised', 'rv.gained')
                ->whereRaw('rv.gained > 0')
                ->orderByRaw('rv.gained::numeric / rv.revised desc, rv.gained desc')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', trans_choice(':count vote passé à oui|:count votes passés à oui', (int) $p->getAttribute('gained')).' après lecture des arguments, sur '.trans_choice(':count révision|:count révisions', (int) $p->getAttribute('revised')))),

            'arbitrages' => $base
                ->joinSub(
                    DB::table('tradeoff_items as ti')
                        ->join('tradeoffs as t', 't.id', '=', 'ti.tradeoff_id')
                        ->join('tradeoff_answers as ta', 'ta.tradeoff_id', '=', 'ti.tradeoff_id')
                        ->whereIn('t.status', ['open', 'closed'])
                        ->selectRaw('ti.proposal_id, count(*) filter (where ta.item_ids @> jsonb_build_array(ti.id)) as chosen, count(*) as answers')
                        ->groupBy('ti.proposal_id'),
                    'tr', 'tr.proposal_id', '=', 'proposals.id'
                )
                ->select('proposals.*', 'tr.chosen', 'tr.answers')
                ->whereRaw('tr.chosen > 0')
                ->orderByRaw('tr.chosen::numeric / tr.answers desc, tr.chosen desc, proposals.id asc')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', 'choisie dans '.VoteService::percent((int) $p->getAttribute('chosen'), (int) $p->getAttribute('answers')).' % des arbitrages ('.$p->getAttribute('chosen').' sur '.$p->getAttribute('answers').')')),

            'consensuelles' => $runId === null ? collect() : $this->byConsensus($base, $runId, 'score')
                ->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', sprintf('accord d’au moins %d %% dans chacun des %d groupes de votants', (int) round(100 * (float) $p->getAttribute('consensus_score')), (int) $p->getAttribute('consensus_groups')))),

            default => $base->latest()->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', trans_choice(':count vote|:count votes', $p->votes_count))),
        };
    }

    /**
     * Propositions notées par le calcul `$runId`, triées par `$column` décroissant puis par ancienneté.
     *
     * @param  Builder<Proposal>  $base
     * @return Builder<Proposal>
     */
    private function byConsensus(Builder $base, int $runId, string $column): Builder
    {
        return $base
            ->join('consensus_scores as cs', fn ($j) => $j->on('cs.proposal_id', '=', 'proposals.id')->where('cs.run_id', '=', $runId))
            ->whereNotNull("cs.{$column}")
            ->select('proposals.*')
            ->selectRaw('cs.score as consensus_score, cs.divisiveness as consensus_divisiveness')
            ->selectRaw("(select count(*) from json_array_elements(cs.groups) g where (g->>'represented')::boolean) as consensus_groups")
            ->orderByDesc("cs.{$column}")
            ->orderBy('proposals.id');
    }

    /**
     * @param  Builder<Proposal>  $base
     * @return Builder<Proposal>
     */
    private function withVoteStats(Builder $base, int $minVotes): Builder
    {
        return $base
            ->joinSub(
                Vote::query()
                    ->selectRaw('proposal_id, count(*) as total, sum(case when desirable = 1 then 1 else 0 end) as yes, sum(case when desirable = -1 then 1 else 0 end) as no, sum(case when necessary = 1 then 1 else 0 end) as nec_yes')
                    ->groupBy('proposal_id')
                    ->havingRaw('count(*) >= ?', [$minVotes]),
                'vs', 'vs.proposal_id', '=', 'proposals.id'
            )
            ->select('proposals.*', 'vs.total', 'vs.yes', 'vs.no', 'vs.nec_yes');
    }
}
