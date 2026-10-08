<?php

namespace App\Services;

use App\Enums\ArgumentSide;
use App\Enums\Milestone;
use App\Models\Argument;
use App\Models\Theme;
use App\Models\TradeoffAnswer;
use App\Models\User;
use App\Models\UserMilestone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Parcours personnel (lot 6) : compteurs et jalons d'un participant, calculés à partir des
 * données existantes (votes, arguments, marques « utile », arbitrages, propositions).
 *
 * Règles opposables : tout est privé (visible du seul participant), aucun jalon ne porte sur un
 * volume, aucune règle ne regarde le sens d'un vote, rien n'entre dans les classements.
 */
class Journey
{
    /**
     * Compteurs personnels.
     *
     * @return array{votes: int, themes: int, themes_total: int, useful: int, answers: int, revised: int, arguments: int, proposals: int, since: CarbonInterface|null}
     */
    public function stats(User $user): array
    {
        return [
            'votes' => $user->votes()->count(),
            'themes' => $this->rootThemesVoted($user),
            'themes_total' => Theme::query()->listed()->roots()->count(),
            'useful' => $user->markedArguments()->count(),
            'answers' => $user->tradeoffAnswers()->count(),
            'revised' => $user->votes()->where('revised_after_arguments', true)->count(),
            'arguments' => $user->arguments()->published()->count(),
            'proposals' => $user->proposals()->published()->count(),
            'since' => $user->created_at,
        ];
    }

    /**
     * Évalue les jalons non encore atteints et enregistre ceux qui le sont désormais.
     * Idempotent : un jalon n'est écrit qu'une fois.
     *
     * @return list<Milestone> Jalons nouvellement atteints
     */
    public function evaluate(User $user): array
    {
        $reached = $this->reached($user);
        $fresh = [];

        foreach (Milestone::cases() as $milestone) {
            if (isset($reached[$milestone->value]) || ! $this->satisfies($user, $milestone)) {
                continue;
            }

            DB::table('milestones')->insertOrIgnore([
                'participant_id' => $user->id,
                'key' => $milestone->value,
                'reached_at' => now(),
            ]);

            $fresh[] = $milestone;
        }

        return $fresh;
    }

    /**
     * @return array<string, Carbon> clé du jalon => date
     */
    public function reached(User $user): array
    {
        return UserMilestone::query()
            ->where('participant_id', $user->id)
            ->get()
            ->mapWithKeys(fn (UserMilestone $m) => [$m->key->value => $m->reached_at])
            ->all();
    }

    /**
     * Jalons atteints mais pas encore célébrés ; ils sont marqués vus dans la foulée.
     *
     * @return list<Milestone>
     */
    public function takeFresh(User $user): array
    {
        $fresh = array_values(UserMilestone::query()
            ->where('participant_id', $user->id)
            ->whereNull('seen_at')
            ->orderBy('reached_at')
            ->get()
            ->map(fn (UserMilestone $m): Milestone => $m->key)
            ->all());

        if ($fresh !== []) {
            DB::table('milestones')->where('participant_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
        }

        return $fresh;
    }

    /**
     * Parcours de démarrage : trois pas, dérivés des jalons.
     *
     * @return list<array{milestone: Milestone, done: bool}>
     */
    public function onboarding(User $user): array
    {
        $reached = $this->reached($user);

        return array_map(fn (Milestone $m) => ['milestone' => $m, 'done' => isset($reached[$m->value])], [
            Milestone::FirstVoice,
            Milestone::FullReading,
            Milestone::Arbiter,
        ]);
    }

    private function satisfies(User $user, Milestone $milestone): bool
    {
        return match ($milestone) {
            Milestone::FirstVoice => $user->votes()->exists(),
            Milestone::FullReading => $user->votes()->where('after_arguments', true)->exists(),
            Milestone::TwoViewpoints => $user->markedArguments()->where('side', ArgumentSide::For->value)->exists()
                && $user->markedArguments()->where('side', ArgumentSide::Against->value)->exists(),
            Milestone::OpenMind => $user->votes()->where('revised_after_arguments', true)->exists(),
            Milestone::Nuance => $user->votes()->whereNotNull('condition')->exists(),
            Milestone::Arbiter => TradeoffAnswer::query()->where('participant_id', $user->id)->exists(),
            Milestone::Exploration => $this->rootThemesVoted($user) >= (int) config('votalis.journey.exploration_themes', 5),
            Milestone::Sourced => Argument::query()->published()->where('author_id', $user->id)->whereNotNull('source_url')->exists(),
            Milestone::Proposer => $user->proposals()->published()->exists(),
        };
    }

    /** Nombre de thèmes de premier niveau dans lesquels le participant a voté. */
    private function rootThemesVoted(User $user): int
    {
        return (int) DB::table('votes')
            ->where('votes.participant_id', $user->id)
            ->join('proposals', 'proposals.id', '=', 'votes.proposal_id')
            ->join('themes', 'themes.id', '=', 'proposals.theme_id')
            ->selectRaw('count(distinct coalesce(themes.parent_id, themes.id)) as n')
            ->value('n');
    }
}
