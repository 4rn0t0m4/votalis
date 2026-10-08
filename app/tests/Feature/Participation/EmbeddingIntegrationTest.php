<?php

namespace Tests\Feature\Participation;

use App\Services\EmbeddingClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Critère C2 du lot 3, vérifié contre le vrai service d'embeddings quand il est joignable
 * (docker compose up embeddings). Ignoré sinon : les autres tests simulent le service.
 */
#[Group('embeddings')]
class EmbeddingIntegrationTest extends TestCase
{
    public function test_baisser_les_charges_des_pme_est_proche_d_alleger_les_cotisations_des_petites_entreprises(): void
    {
        Http::preventStrayRequests(false);
        Http::fake(); // vide : laisse passer les vrais appels
        Http::swap(new Factory);

        $client = app(EmbeddingClient::class);

        if (! $client->isAvailable()) {
            $this->markTestSkipped('Service d’embeddings non joignable.');
        }

        $vectors = $client->embed([
            'Baisser les charges des PME',
            'Alléger les cotisations des petites entreprises',
            'Créer mille lits d’hôpital supplémentaires',
        ]);

        $this->assertNotNull($vectors);
        $this->assertCount((int) config('votalis.embeddings.dimension'), $vectors[0]);

        $close = EmbeddingClient::cosine($vectors[0], $vectors[1]);
        $far = EmbeddingClient::cosine($vectors[0], $vectors[2]);

        $this->assertGreaterThanOrEqual((float) config('votalis.duplicates.threshold'), $close, "Similarité PME/petites entreprises : {$close}");
        $this->assertLessThan((float) config('votalis.duplicates.threshold'), $far, "Similarité PME/lits d’hôpital : {$far}");
    }
}
