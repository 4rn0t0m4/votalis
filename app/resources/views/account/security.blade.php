<x-layouts.app title="Sécurité du compte">
    <h1 class="text-2xl font-semibold">Sécurité du compte</h1>

    @if ($user->requiresTwoFactor() && ! $user->hasSecondFactor())
        <x-alert type="error" class="mt-4">
            Votre rôle « {{ $user->role->label() }} » exige une double authentification. Activez une application d'authentification ou une clé d'accès pour retrouver l'accès complet.
        </x-alert>
    @endif

    <section class="mt-8 max-w-xl rounded-lg border border-ink-200 bg-white p-6" aria-labelledby="totp">
        <h2 id="totp" class="text-lg font-semibold">Application d'authentification (TOTP)</h2>

        @if ($twoFactorEnabled)
            <p class="mt-2 text-sm text-accent-700">Activée.</p>

            @if (session('status') === 'two-factor-authentication-confirmed' || session('status') === 'recovery-codes-generated')
                <h3 class="mt-4 font-medium">Codes de secours</h3>
                <p class="text-sm text-ink-700">Conservez-les en lieu sûr : chacun ne sert qu'une fois.</p>
                <ul class="mt-2 grid grid-cols-2 gap-1 font-mono text-sm">
                    @foreach ($user->recoveryCodes() as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 flex gap-3">
                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                    @csrf
                    <x-button variant="secondary">Regénérer les codes de secours</x-button>
                </form>
                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf @method('DELETE')
                    <x-button variant="danger">Désactiver</x-button>
                </form>
            </div>
        @elseif ($twoFactorPending)
            <p class="mt-2 text-sm text-ink-700">Scannez ce code avec votre application d'authentification, puis saisissez le code à six chiffres qu'elle affiche.</p>
            <div class="mt-4 inline-block rounded border border-ink-200 bg-white p-2" aria-label="Code QR de configuration">
                {!! $user->twoFactorQrCodeSvg() !!}
            </div>
            <p class="mt-2 text-sm text-ink-500">Saisie manuelle : <code class="select-all">{{ decrypt($user->two_factor_secret) }}</code></p>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-4">
                @csrf
                <x-form.field name="code" label="Code à six chiffres" required autocomplete="one-time-code" inputmode="numeric" bag="confirmTwoFactorAuthentication" />
                <x-button>Confirmer l'activation</x-button>
            </form>
        @else
            <p class="mt-2 text-sm text-ink-700">Un code temporaire généré par une application (Aegis, FreeOTP, Google Authenticator…) sera demandé à chaque connexion.</p>
            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">
                @csrf
                <x-button>Activer</x-button>
            </form>
        @endif
    </section>

    <section class="mt-6 max-w-xl rounded-lg border border-ink-200 bg-white p-6" aria-labelledby="passkeys">
        <h2 id="passkeys" class="text-lg font-semibold">Clés d'accès (WebAuthn)</h2>
        <p class="mt-2 text-sm text-ink-700">Clé de sécurité physique, empreinte ou code de votre appareil. Une clé d'accès vaut second facteur et permet aussi de se connecter sans mot de passe.</p>

        @if ($passkeys->isNotEmpty())
            <ul class="mt-4 divide-y divide-ink-200 text-sm">
                @foreach ($passkeys as $passkey)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $passkey->name }} <span class="text-ink-500">· ajoutée le {{ $passkey->created_at?->translatedFormat('j F Y') }}</span></span>
                        <form method="POST" action="{{ route('passkey.destroy', $passkey) }}">
                            @csrf @method('DELETE')
                            <x-button variant="danger" class="px-2 py-1">Supprimer</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4">
            <label for="passkey-name" class="mb-1 block text-sm font-medium">Nom de la clé</label>
            <div class="flex gap-2">
                <input id="passkey-name" type="text" maxlength="60" placeholder="Ex. Clé USB, Téléphone" class="block w-full rounded border border-ink-300 px-3 py-2">
                <x-button type="button" data-passkey-register="#passkey-name" data-passkey-error="#passkey-erreur">Ajouter</x-button>
            </div>
            <p id="passkey-erreur" role="alert" hidden class="mt-2 text-sm text-red-800"></p>
        </div>
    </section>
</x-layouts.app>
