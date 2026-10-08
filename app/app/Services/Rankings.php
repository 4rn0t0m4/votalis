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

    /** Onglets dont le calcul n'est pas encore disponible au MVP. */
    public static function pending(string $tab): ?string
    {
        return match ($tab) {
            'consensuelles' => 'Le classement par consensus (familles de votants) arrive en V2, une fois quelques centaines de votants actifs atteints.',
            default => null,
        };
    }

    /**
     * @return Collection<int, Proposal> Propositions avec un attribut `metric` (texte affiché)
     */
    public function forTheme(Theme $theme, string $tab): Collection
    {
        $themeIds = array_values(array_map('intval', $theme->children()->pluck('id')->push($theme->id)->all()));
        $key = "rankings:{$theme->id}:{$tab}";

        /** @var Collection<int, Proposal> */
        return Cache::remember($key, now()->addSeconds((int) config('votalis.rankings.cache_seconds', 300)), fn () => $this->compute($themeIds, $tab));
    }

    /**
     * @param  list<int>  $themeIds
     * @return Collection<int, Proposal>
     */
    private function compute(array $themeIds, string $tab): Collection
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

            'clivantes' => $this->withVoteStats($base, $minVotes)
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

            default => $base->latest()->limit($limit)->get()
                ->each(fn (Proposal $p) => $p->setAttribute('metric', trans_choice(':count vote|:count votes', $p->votes_count))),
        };
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
