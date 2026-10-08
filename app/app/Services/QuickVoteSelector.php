<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;

/**
 * Vote rapide (CDC 4.3) : une proposition à la fois, non encore votée, tirée au sort avec un
 * poids plus fort pour les propositions récentes et peu votées. Jamais ses propres fiches.
 */
class QuickVoteSelector
{
    /**
     * @param  list<int>  $skipped  Fiches passées pendant la session, à ne pas reproposer tout de suite
     */
    public function next(User $user, array $skipped = []): ?Proposal
    {
        $candidates = Proposal::query()
            ->published()
            ->where(fn ($q) => $q->whereNull('author_id')->orWhere('author_id', '!=', $user->id))
            ->whereDoesntHave('votes', fn ($q) => $q->where('participant_id', $user->id))
            ->when($skipped !== [], fn ($q) => $q->whereKeyNot($skipped))
            ->inRandomOrder()
            ->limit((int) config('votalis.quick_vote.sample_size', 200))
            ->get(['id', 'created_at', 'votes_count']);

        if ($candidates->isEmpty()) {
            return $skipped === [] ? null : $this->next($user);
        }

        $weights = $candidates->map(fn (Proposal $p) => $this->weight($p));
        $draw = random_int(1, max(1, (int) $weights->sum()));

        foreach ($candidates as $index => $candidate) {
            $draw -= $weights[$index];

            if ($draw <= 0) {
                return Proposal::query()->with(['theme.parent', 'sources', 'author'])->find($candidate->id);
            }
        }

        return Proposal::query()->with(['theme.parent', 'sources', 'author'])->find($candidates->last()->id);
    }

    public function weight(Proposal $proposal): int
    {
        $weight = 1;

        if ($proposal->created_at !== null && $proposal->created_at->gt(now()->subDays((int) config('votalis.quick_vote.recent_days', 14)))) {
            $weight *= (int) config('votalis.quick_vote.recent_weight', 3);
        }

        if ($proposal->votes_count < (int) config('votalis.quick_vote.low_votes_threshold', 10)) {
            $weight *= (int) config('votalis.quick_vote.low_votes_weight', 2);
        }

        return max(1, $weight);
    }
}
