<x-layouts.app title="Double authentification">
    <x-auth-card title="Double authentification">
        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <x-form.field name="code" label="Code de votre application d'authentification" autocomplete="one-time-code" inputmode="numeric" />
            <p class="mb-4 text-sm text-ink-700">Ou utilisez un code de secours :</p>
            <x-form.field name="recovery_code" label="Code de secours" autocomplete="off" />
            <x-button class="w-full">Valider</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
