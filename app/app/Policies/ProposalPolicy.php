<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    /** Participant vérifié uniquement : l'administrateur technique n'a pas la capacité « participate ». */
    public function create(User $user): bool
    {
        return $user->can('participate') && $user->hasVerifiedEmail();
    }

    /** L'auteur corrige sa fiche tant qu'elle est publiée ; le fond est verrouillé après le premier vote (service). */
    public function update(User $user, Proposal $proposal): bool
    {
        return $this->create($user)
            && $proposal->author_id === $user->id
            && $proposal->status === ProposalStatus::Published;
    }
}
