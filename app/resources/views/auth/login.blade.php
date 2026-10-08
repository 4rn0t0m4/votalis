<x-layouts.app title="Se connecter">
    <x-auth-card title="Se connecter">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <x-form.field name="identifier" label="Pseudonyme ou adresse e-mail" required autocomplete="username" />
            <x-form.field name="password" label="Mot de passe" type="password" required autocomplete="current-password" />
            <div class="mb-4 flex items-center gap-2">
                <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-ink-300">
                <label for="remember" class="text-sm">Rester connecté sur cet appareil</label>
            </div>
            <x-button class="w-full">Se connecter</x-button>
        </form>

        <div class="mt-4">
            <button type="button" data-passkey-login="{{ route('home') }}" data-passkey-error="#passkey-erreur"
                class="w-full rounded border border-ink-300 bg-white px-4 py-2 text-sm font-medium hover:bg-ink-100">
                Se connecter avec une clé d'accès
            </button>
            <p id="passkey-erreur" role="alert" hidden class="mt-2 text-sm text-red-800"></p>
        </div>

        <x-slot:footer>
            <p><a href="{{ route('password.request') }}" class="underline">Mot de passe oublié ?</a></p>
            <p class="mt-1">Pas encore de compte ? <a href="{{ route('register') }}" class="underline">Créer un compte</a></p>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
