<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache court des pages publiques pour les visiteurs non connectés (CDC section 7, résilience).
 * Clé versionnée : `flush()` incrémente la version, ce qui invalide tout d'un coup, quel que
 * soit le magasin de cache. Les réponses des utilisateurs connectés ne sont jamais mises en cache.
 */
class PublicPageCache
{
    private const VERSION_KEY = 'public-pages.version';

    private const HEADERS = ['Content-Type', 'Content-Security-Policy'];

    public function ttl(): int
    {
        return (int) config('votalis.cache.public_seconds', 60);
    }

    public function cacheable(Request $request): bool
    {
        if ($this->ttl() <= 0 || ! $request->isMethod('GET') || $request->user() !== null) {
            return false;
        }

        // Une page portant un message de session (statut, erreurs) est personnelle : pas de cache.
        return ! $request->hasSession()
            || ($request->session()->get('status') === null && ! $request->session()->has('errors'));
    }

    public function get(Request $request): ?Response
    {
        /** @var array{status: int, content: string, headers: array<string, string>}|null $cached */
        $cached = Cache::get($this->key($request));

        if ($cached === null) {
            return null;
        }

        $response = new \Illuminate\Http\Response($cached['content'], $cached['status']);
        foreach ($cached['headers'] as $name => $value) {
            $response->headers->set($name, $value);
        }
        $response->headers->set('X-Cache', 'HIT');

        return $response;
    }

    public function put(Request $request, Response $response): void
    {
        if ($response->getStatusCode() !== 200) {
            return;
        }

        $headers = [];
        foreach (self::HEADERS as $name) {
            if ($response->headers->has($name)) {
                $headers[$name] = (string) $response->headers->get($name);
            }
        }

        Cache::put($this->key($request), [
            'status' => 200,
            'content' => (string) $response->getContent(),
            'headers' => $headers,
        ], $this->ttl());

        $response->headers->set('X-Cache', 'MISS');
    }

    /** Invalide toutes les pages publiques (appelé après une action de modération ou une publication). */
    public function flush(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    private function key(Request $request): string
    {
        $version = Cache::get(self::VERSION_KEY, 0);

        return 'public-pages:'.$version.':'.sha1($request->fullUrl());
    }
}
