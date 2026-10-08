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

    /**
     * L'auteur corrige sa fiche tant qu'elle est publiée ; le fond est verrouillé après le
     * premier vote (service). Une reformulation demandée par la modération rouvre la fiche
     * à son auteur jusqu'à l'échéance.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        if (! $this->create($user) || $proposal->author_id !== $user->id) {
            return false;
        }

        if ($proposal->status === ProposalStatus::RewriteRequested) {
            return $proposal->rewrite_allowed_until?->isFuture() === true;
        }

        return $proposal->status === ProposalStatus::Published;
    }

    /** Un contenu masqué reste lisible par son auteur et par la modération. */
    public function viewHidden(?User $user, Proposal $proposal): bool
    {
        return $user !== null && ($user->can('moderate') || $proposal->author_id === $user->id);
    }
}
