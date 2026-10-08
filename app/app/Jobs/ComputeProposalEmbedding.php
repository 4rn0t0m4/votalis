<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\DuplicateFinder;
use App\Services\EmbeddingClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Calcule et enregistre le vecteur sémantique d'une fiche (titre + mesure). */
class ComputeProposalEmbedding implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $proposalId) {}

    public function handle(EmbeddingClient $client): void
    {
        $proposal = Proposal::query()->find($this->proposalId);

        if ($proposal === null) {
            return;
        }

        $vector = $client->embedOne(DuplicateFinder::text($proposal->title, $proposal->measure));

        if ($vector === null) {
            $this->release(60);

            return;
        }

        Proposal::query()->whereKey($proposal->id)->update([
            'embedding' => EmbeddingClient::literal($vector),
            'embedding_version' => (string) config('votalis.embeddings.version'),
            'embedded_at' => now(),
        ]);
    }
}
