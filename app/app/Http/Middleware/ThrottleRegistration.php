<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limitation de débit de l'inscription (CDC 7.1). Fortify n'expose pas de limiteur
 * pour cette route : on applique le limiteur nommé `register` à la soumission du formulaire.
 */
class ThrottleRegistration
{
    public function __construct(private readonly ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register.store')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, 'register');
    }
}
