<?php

namespace Tests\Feature\Participation;

use App\Services\EmbeddingClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EmbeddingClientTest extends TestCase
{
    public function test_appelle_le_service_interne_et_retourne_les_vecteurs(): void
    {
        Http::fake(['*/embed' => Http::response(['model' => 'x', 'dimension' => 3, 'vectors' => [[1, 0, 0], [0, 1, 0]]])]);

        $vectors = app(EmbeddingClient::class)->embed(['a', 'b']);

        $this->assertSame([[1, 0, 0], [0, 1, 0]], $vectors);
        Http::assertSent(fn ($request) => $request['texts'] === ['a', 'b'] && $request['kind'] === 'query');
    }

    public function test_refuse_un_hote_hors_du_reseau_prive(): void
    {
        config(['votalis.embeddings.url' => 'https://api.exemple-externe.com']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non autorisé');
        app(EmbeddingClient::class)->embed(['texte confidentiel']);
    }

    public function test_degrade_silencieusement_si_le_service_est_indisponible(): void
    {
        Http::fake(['*/*' => Http::response('erreur', 503)]);

        $this->assertNull(app(EmbeddingClient::class)->embed(['a']));
        $this->assertNull(app(EmbeddingClient::class)->embedOne('a'));
    }

    public function test_cosinus_et_litteral_pgvector(): void
    {
        $this->assertEqualsWithDelta(1.0, EmbeddingClient::cosine([1, 0], [1, 0]), 1e-9);
        $this->assertEqualsWithDelta(0.0, EmbeddingClient::cosine([1, 0], [0, 1]), 1e-9);
        $this->assertSame('[0.5,-0.25,1]', EmbeddingClient::literal([0.5, -0.25, 1.0]));
    }
}
