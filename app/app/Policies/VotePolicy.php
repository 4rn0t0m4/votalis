<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\User;

class VotePolicy
{
    /** Participant vérifié, jamais sur sa propre proposition. */
    public function vote(User $user, Proposal $proposal): bool
    {
        return $user->can('participate')
            && $user->hasVerifiedEmail()
            && $proposal->status === ProposalStatus::Published
            && $proposal->author_id !== $user->id;
    }
}
