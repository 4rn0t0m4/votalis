<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class AssignRole extends Command
{
    protected $signature = 'role:assign {pseudonym : Pseudonyme du compte} {role : participant, moderator, editorial ou admin}';

    protected $description = 'Attribue un rôle à un compte (seule voie d\'attribution au lot 1)';

    public function handle(): int
    {
        $role = Role::tryFrom((string) $this->argument('role'));

        if ($role === null) {
            $this->error('Rôle inconnu. Valeurs : '.implode(', ', array_map(fn (Role $r) => $r->value, Role::cases())));

            return self::FAILURE;
        }

        $pseudonym = (string) $this->argument('pseudonym');
        $user = User::query()->whereRaw('lower(pseudonym) = ?', [mb_strtolower($pseudonym)])->first();

        if ($user === null) {
            $this->error("Aucun compte avec le pseudonyme « {$pseudonym} ».");

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();

        $this->info("Rôle « {$role->label()} » attribué à {$user->pseudonym}.");

        if ($role->isPrivileged() && ! $user->hasSecondFactor()) {
            $this->warn('Ce rôle exige une double authentification : le compte sera bloqué jusqu\'à son activation.');
        }

        return self::SUCCESS;
    }
}
