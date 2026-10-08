<?php

namespace App\Policies;

use App\Models\Argument;
use App\Models\User;

class ArgumentPolicy
{
    public function create(User $user): bool
    {
        return $user->can('participate') && $user->hasVerifiedEmail();
    }

    public function mark(User $user, Argument $argument): bool
    {
        return $this->create($user);
    }
}
