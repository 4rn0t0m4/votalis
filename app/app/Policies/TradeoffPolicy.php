<?php

namespace App\Policies;

use App\Models\Tradeoff;
use App\Models\User;

class TradeoffPolicy
{
    /** Le comité éditorial définit les arbitrages (CDC 4.9). */
    public function manage(User $user): bool
    {
        return $user->can('manage-tradeoffs');
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Tradeoff $tradeoff): bool
    {
        return $this->manage($user);
    }

    /** Composer sa combinaison : participant vérifié, exercice ouvert. */
    public function answer(User $user, Tradeoff $tradeoff): bool
    {
        return $user->can('participate') && $user->hasVerifiedEmail() && $tradeoff->isOpen();
    }

    public function view(?User $user, Tradeoff $tradeoff): bool
    {
        return $tradeoff->isPublic() || ($user !== null && $this->manage($user));
    }
}
