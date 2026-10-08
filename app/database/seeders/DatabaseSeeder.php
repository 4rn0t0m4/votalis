<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de développement uniquement : jamais exécuté en production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        User::factory()->create(['pseudonym' => 'participante', 'email' => 'participante@example.test']);
        User::factory()->role(Role::Moderator)->withTwoFactor()->create(['pseudonym' => 'moderateur', 'email' => 'moderateur@example.test']);
        User::factory()->role(Role::Editorial)->withTwoFactor()->create(['pseudonym' => 'comite', 'email' => 'comite@example.test']);
        User::factory()->role(Role::Admin)->withTwoFactor()->create(['pseudonym' => 'admin', 'email' => 'admin@example.test']);
    }
}
