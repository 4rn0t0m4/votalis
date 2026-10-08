<?php

namespace App\Policies;

use App\Models\ModerationLogEntry;
use App\Models\User;

class AppealPolicy
{
    /** L'auteur du contenu (ou le titulaire du compte suspendu), une fois, dans le délai. Un compte suspendu peut contester. */
    public function appeal(User $user, ModerationLogEntry $entry): bool
    {
        return $user->hasVerifiedEmail() && $entry->isAppealableBy($user) && ! $entry->appeal()->exists();
    }
}
