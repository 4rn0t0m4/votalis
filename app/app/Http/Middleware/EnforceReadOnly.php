<?php

namespace App\Http\Middleware;

use App\Services\ReadOnlyMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En lecture seule, toute requête d'écriture est refusée (503), y compris les appels Livewire,
 * sauf connexion, déconnexion, second facteur, mot de passe et administration.
 */
class EnforceReadOnly
{
    private const ALLOWED_ROUTES = [
        'login',
        'logout',
        'two-factor.login',
        'password.email',
        'password.update',
        'password.confirm.store',
        'passkey.login-options',
        'passkey.login',
        'admin.read-only',
    ];

    public function __construct(private readonly ReadOnlyMode $readOnly) {}

    /** Chemins Fortify (connexion, déconnexion, second facteur, mot de passe), clés d'accès et administration. */
    private function isAuthPath(Request $request): bool
    {
        $paths = array_filter([
            config('fortify.paths.login'),
            config('fortify.paths.logout'),
            config('fortify.paths.two-factor.login'),
            config('fortify.paths.password.email'),
            config('fortify.paths.password.update'),
            config('fortify.paths.password.confirm'),
        ], 'is_string');

        $patterns = array_map(fn (string $p) => trim($p, '/'), $paths);
        $patterns[] = 'passkeys/*';
        $patterns[] = 'admin/*';

        return $request->is(...$patterns);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $this->readOnly->enabled()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true) || $this->isAuthPath($request)) {
            return $next($request);
        }

        $message = $this->readOnly->message();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        return response()->view('read-only', ['message' => $message], 503);
    }
}
