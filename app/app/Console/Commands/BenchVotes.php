<?php

namespace App\Console\Commands;

use App\Enums\VoteValue;
use App\Models\Proposal;
use App\Models\User;
use App\Services\VoteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mesure côté serveur du temps d'enregistrement d'un vote (CDC 11 : < 300 ms), en appelant
 * VoteService comme le ferait l'interface. Réservé aux environnements non productifs :
 * crée des comptes et des votes de test, supprimés à la fin.
 */
class BenchVotes extends Command
{
    protected $signature = 'votalis:bench-votes {--count=500 : Nombre de votes à enregistrer}';

    protected $description = 'Mesure la latence serveur de l’enregistrement des votes (hors production)';

    public function handle(VoteService $votes): int
    {
        if (app()->isProduction()) {
            $this->error('Interdit en production.');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $proposals = Proposal::query()->published()->inRandomOrder()->limit(50)->get();

        if ($proposals->isEmpty()) {
            $this->error('Aucune proposition publiée.');

            return self::FAILURE;
        }

        $users = User::factory()->count(min($count, 100))->create(['pseudonym' => fn () => 'bench_'.Str::lower(Str::random(10)), 'created_at' => now()->subMonth()]);
        $durations = [];
        $done = 0;

        foreach ($users as $user) {
            foreach ($proposals->shuffle()->take((int) ceil($count / $users->count())) as $proposal) {
                if ($done >= $count) {
                    break 2;
                }
                $start = hrtime(true);
                $votes->cast($user, $proposal, VoteValue::Yes, VoteValue::Unsure, null, false);
                $durations[] = (hrtime(true) - $start) / 1e6;
                $done++;
            }
        }

        sort($durations);
        $p = fn (float $q): float => $durations[(int) floor(($q / 100) * (count($durations) - 1))];

        $this->table(['votes', 'p50 (ms)', 'p95 (ms)', 'p99 (ms)', 'max (ms)'], [[
            count($durations), number_format($p(50), 1), number_format($p(95), 1), number_format($p(99), 1), number_format($p(100), 1),
        ]]);

        // Nettoyage : la suppression des comptes efface les votes en cascade ; compteurs recalés.
        $proposalIds = $proposals->pluck('id');
        User::query()->whereIn('id', $users->pluck('id'))->delete();
        foreach ($proposalIds as $id) {
            Proposal::query()->whereKey($id)->update(['votes_count' => DB::table('votes')->where('proposal_id', $id)->count()]);
        }

        return $p(95) < 300 ? self::SUCCESS : self::FAILURE;
    }
}
