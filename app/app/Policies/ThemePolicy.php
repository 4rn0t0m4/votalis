<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;

/** Seul le comité éditorial crée et réorganise les thèmes (CDC section 3). */
class ThemePolicy
{
    public function manage(User $user): bool
    {
        return $user->can('manage-themes');
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Theme $theme): bool
    {
        return $this->manage($user);
    }
}
