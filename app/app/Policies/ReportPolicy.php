<?php

namespace App\Policies;

use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;

class ReportPolicy
{
    /** Participant vérifié, qui n'est pas l'auteur du contenu ; le contenu doit être visible. */
    public function report(User $user, Proposal|Argument $target): bool
    {
        return $user->can('participate')
            && $user->hasVerifiedEmail()
            && $target->author_id !== $user->id
            && $target->isPublished();
    }
}
