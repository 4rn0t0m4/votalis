<?php

namespace Database\Seeders;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Développement uniquement (lot 7, Q8) : votants fictifs pour franchir les seuils du classement par
 * consensus et le montrer. Trois profils d'opinion synthétiques, tirés avec une graine fixe, sans
 * rapport avec aucune sensibilité réelle : chaque profil « aime » un tiers des fiches, en « rejette »
 * un tiers et répond au hasard sur le reste ; dix fiches « consensuelles » plaisent à tous.
 * Les comptes sont nommés `votant-fictif-NNN` et recréés à chaque `migrate:fresh --seed`.
 */
class DemoVotersSeeder extends Seeder
{
    public const ACCOUNTS = 400;

    private const FAMILIES = 3;

    public function run(): void
    {
        if (app()->isProduction() || User::query()->where('pseudonym', 'like', 'votant-fictif-%')->exists()) {
            return;
        }

        $proposals = Proposal::query()->published()->orderBy('id')->pluck('id')->all();

        if (count($proposals) < 20) {
            return;
        }

        $random = new Randomizer(new Mt19937(20261009));
        $consensual = array_slice($proposals, 0, 10);
        $others = array_slice($proposals, 10);
        $now = now();
        $rows = [];

        foreach (range(1, self::ACCOUNTS) as $i) {
            $family = $i % self::FAMILIES;
            $user = User::factory()->create([
                'pseudonym' => sprintf('votant-fictif-%03d', $i),
                'email' => sprintf('votant-fictif-%03d@example.test', $i),
                'created_at' => $now->copy()->subDays(30 + $random->getInt(0, 60)),
            ]);

            $count = $random->getInt(15, 40);
            $picked = $random->pickArrayKeys($proposals, min($count, count($proposals)));

            foreach ($picked as $key) {
                $proposal = $proposals[$key];
                $draw = $random->getInt(0, 99);

                if (in_array($proposal, $consensual, true)) {
                    $desirable = $draw < 85 ? 1 : ($draw < 95 ? -1 : 0);
                } else {
                    $index = (int) array_search($proposal, $others, true);
                    $liked = $index % self::FAMILIES === $family;
                    $desirable = $liked ? ($draw < 80 ? 1 : ($draw < 92 ? 0 : -1)) : ($draw < 20 ? 1 : ($draw < 85 ? -1 : 0));
                }

                // « Nécessaire » suit « souhaitable », avec un peu plus de oui (mesures jugées utiles mais coûteuses).
                $necessary = $desirable === 1 ? 1 : ($random->getInt(0, 99) < 25 ? 1 : $desirable);

                $rows[] = [
                    'participant_id' => $user->id, 'proposal_id' => $proposal,
                    'desirable' => $desirable, 'necessary' => $necessary,
                    'desirable_initial' => $desirable, 'necessary_initial' => $necessary,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('votes')->insert($chunk);
        }

        DB::statement('update proposals p set votes_count = (select count(*) from votes v where v.proposal_id = p.id)');

        $this->command->info(sprintf('%d votants fictifs, %d votes.', self::ACCOUNTS, count($rows)));
    }
}
