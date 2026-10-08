<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client du service d'embeddings interne. Les textes ne quittent jamais le réseau privé :
 * l'hôte du service doit figurer dans la liste autorisée. En cas d'indisponibilité, retourne
 * null : l'appelant dégrade silencieusement (pas de suggestion) plutôt que d'échouer.
 */
class EmbeddingClient
{
    /**
     * @param  list<string>  $texts
     * @return list<list<float>>|null
     */
    public function embed(array $texts, string $kind = 'query'): ?array
    {
        if ($texts === []) {
            return [];
        }

        $url = $this->url();

        try {
            $response = Http::timeout((float) config('votalis.embeddings.timeout', 5.0))
                ->acceptJson()
                ->post($url.'/embed', ['texts' => $texts, 'kind' => $kind])
                ->throw();

            /** @var list<list<float>> $vectors */
            $vectors = $response->json('vectors', []);

            if (count($vectors) !== count($texts)) {
                Log::warning('Service d’embeddings : nombre de vecteurs inattendu.', ['attendu' => count($texts), 'reçu' => count($vectors)]);

                return null;
            }

            return $vectors;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Service d’embeddings indisponible.', ['erreur' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return list<float>|null
     */
    public function embedOne(string $text, string $kind = 'query'): ?array
    {
        $vectors = $this->embed([$text], $kind);

        return $vectors === null ? null : ($vectors[0] ?? null);
    }

    public function isAvailable(): bool
    {
        try {
            return Http::timeout(2)->get($this->url().'/health')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;

        foreach ($a as $i => $value) {
            $dot += $value * ($b[$i] ?? 0.0);
            $na += $value * $value;
            $nb += ($b[$i] ?? 0.0) ** 2;
        }

        return $na === 0.0 || $nb === 0.0 ? 0.0 : $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * @param  list<float>  $vector
     */
    public static function literal(array $vector): string
    {
        return '['.implode(',', array_map(fn (float $v) => rtrim(rtrim(sprintf('%.8F', $v), '0'), '.'), $vector)).']';
    }

    /** URL du service, refusée si son hôte n'est pas autorisé (CDC 9 : rien ne sort du réseau privé). */
    private function url(): string
    {
        $url = rtrim((string) config('votalis.embeddings.url'), '/');
        $host = parse_url($url, PHP_URL_HOST);
        /** @var list<string> $allowed */
        $allowed = array_map('trim', (array) config('votalis.embeddings.allowed_hosts', []));

        if (! is_string($host) || ! in_array($host, $allowed, true)) {
            throw new RuntimeException('Hôte du service d’embeddings non autorisé : '.(is_string($host) ? $host : 'inconnu').'. Les textes ne doivent pas quitter le réseau privé.');
        }

        return $url;
    }
}
