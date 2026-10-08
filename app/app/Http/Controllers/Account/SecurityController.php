<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /** Page d'activation et de gestion du second facteur (TOTP, codes de secours, clés WebAuthn). */
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('account.security', [
            'user' => $user,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $user->two_factor_secret !== null && ! $user->hasEnabledTwoFactorAuthentication(),
            'passkeys' => $user->passkeys()->orderBy('created_at')->get(),
        ]);
    }
}
