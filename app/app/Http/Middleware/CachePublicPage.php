<?php

namespace App\Http\Middleware;

use App\Services\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** À poser sur les routes publiques en lecture : sert la copie en cache aux visiteurs non connectés. */
class CachePublicPage
{
    public function __construct(private readonly PublicPageCache $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cache->cacheable($request)) {
            return $next($request);
        }

        $hit = $this->cache->get($request);

        if ($hit !== null) {
            return $hit;
        }

        /** @var Response $response */
        $response = $next($request);
        $this->cache->put($request, $response);

        return $response;
    }
}
