<x-layouts.app title="Nouveau mot de passe">
    <x-auth-card title="Choisir un nouveau mot de passe">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-form.field name="email" label="Adresse e-mail" type="email" required autocomplete="email" :value="$request->email" />
            <x-form.field name="password" label="Nouveau mot de passe" type="password" required autocomplete="new-password" help="12 caractères minimum." />
            <x-form.field name="password_confirmation" label="Confirmer le mot de passe" type="password" required autocomplete="new-password" />
            <x-button class="w-full">Enregistrer</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
