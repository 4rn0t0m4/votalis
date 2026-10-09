<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de développement uniquement : jamais exécuté en production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ThemeSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        $this->account('participante', Role::Participant);
        $this->account('moderateur', Role::Moderator);
        $this->account('comite', Role::Editorial);
        $this->account('admin', Role::Admin);

        if (Proposal::query()->doesntExist()) {
            $this->call(DemoProposalSeeder::class);
        }

        $this->call(DemoTradeoffSeeder::class);
        // Lot 7 : votants fictifs pour pouvoir montrer le classement par consensus (jamais en production).
        $this->call(DemoVotersSeeder::class);
    }

    /** Compte de développement, créé une seule fois. */
    private function account(string $pseudonym, Role $role): void
    {
        if (User::query()->where('pseudonym', $pseudonym)->exists()) {
            return;
        }

        $factory = User::factory()->role($role);

        if ($role->isPrivileged()) {
            $factory = $factory->withTwoFactor();
        }

        $factory->create(['pseudonym' => $pseudonym, 'email' => "{$pseudonym}@example.test"]);
    }
}
