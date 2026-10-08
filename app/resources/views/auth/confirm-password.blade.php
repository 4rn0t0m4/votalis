<x-layouts.app title="Confirmer votre mot de passe">
    <x-auth-card title="Confirmez votre mot de passe">
        <p class="mb-4 text-sm text-ink-700">Cette zone est sensible. Confirmez votre mot de passe pour continuer.</p>
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <x-form.field name="password" label="Mot de passe" type="password" required autocomplete="current-password" />
            <x-button class="w-full">Confirmer</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
