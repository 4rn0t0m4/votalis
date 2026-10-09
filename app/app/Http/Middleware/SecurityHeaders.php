<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité (CDC 7.1) : CSP stricte avec nonce, HSTS, etc.
 * Toutes les ressources sont servies par la plateforme : aucune origine tierce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        // Développement seulement : le serveur Vite (origine lue dans public/hot, jamais en dur) sert
        // styles et scripts depuis un autre port, et le rechargement à chaud passe par un websocket.
        $dev = $this->viteDevOrigin();

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'".($dev ? " {$dev}" : ''),
            "style-src 'self' 'nonce-{$nonce}'".($dev ? " {$dev}" : ''),
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'".($dev ? " ws: {$dev}" : ''),
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        return $response;
    }

    /** Origine du serveur Vite de développement (contenu de `public/hot`), ou null hors développement. */
    private function viteDevOrigin(): ?string
    {
        if (! app()->isLocal() || ! Vite::isRunningHot()) {
            return null;
        }

        $url = trim((string) file_get_contents(Vite::hotFile()));

        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
    }
}
