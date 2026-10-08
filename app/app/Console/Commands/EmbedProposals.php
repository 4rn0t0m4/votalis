<?php

namespace App\Console\Commands;

use App\Jobs\ComputeProposalEmbedding;
use App\Models\Proposal;
use Illuminate\Console\Command;

class EmbedProposals extends Command
{
    protected $signature = 'proposals:embed {--all : Recalcule aussi les fiches déjà vectorisées (changement de modèle)}';

    protected $description = 'Met en file le calcul des embeddings des propositions manquantes (ou de toutes)';

    public function handle(): int
    {
        $query = Proposal::query()->published();

        if (! $this->option('all')) {
            $query->where(fn ($q) => $q->whereNull('embedded_at')->orWhere('embedding_version', '!=', (string) config('votalis.embeddings.version')));
        }

        $count = 0;
        $query->select('id')->chunkById(200, function ($proposals) use (&$count): void {
            foreach ($proposals as $proposal) {
                ComputeProposalEmbedding::dispatch($proposal->id);
                $count++;
            }
        });

        $this->info("{$count} calcul(s) mis en file.");

        return self::SUCCESS;
    }
}
