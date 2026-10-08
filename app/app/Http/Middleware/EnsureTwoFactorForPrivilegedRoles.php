<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les rôles modérateur, comité éditorial et administrateur technique ne peuvent
 * rien faire tant qu'un second facteur n'est pas actif (CDC section 3 et 7.1).
 */
class EnsureTwoFactorForPrivilegedRoles
{
    /** Routes accessibles sans second facteur, pour pouvoir l'activer. */
    private const ALLOWED_ROUTES = [
        'security.show',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
        'passkey.registration-options',
        'passkey.store',
        'passkey.destroy',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->requiresTwoFactor() || $user->hasSecondFactor()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        return redirect()
            ->route('security.show')
            ->with('status', __('Votre rôle exige une double authentification. Activez-la pour continuer.'));
    }
}
