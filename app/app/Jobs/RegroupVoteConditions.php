<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\ConditionGrouper;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Recalcule les groupes de conditions d'une proposition après un vote conditionnel. */
class RegroupVoteConditions implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 30;

    public function __construct(public readonly int $proposalId) {}

    public function uniqueId(): string
    {
        return (string) $this->proposalId;
    }

    public function handle(ConditionGrouper $grouper): void
    {
        $proposal = Proposal::query()->find($this->proposalId);

        if ($proposal !== null) {
            $grouper->refresh($proposal);
        }
    }
}
