<x-layouts.app title="Mot de passe oublié">
    <x-auth-card title="Mot de passe oublié">
        <p class="mb-4 text-sm text-ink-700">Indiquez votre adresse e-mail : si un compte lui correspond, vous recevrez un lien de réinitialisation.</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <x-form.field name="email" label="Adresse e-mail" type="email" required autocomplete="email" />
            <x-button class="w-full">Envoyer le lien</x-button>
        </form>
        <x-slot:footer>
            <p><a href="{{ route('login') }}" class="underline">Retour à la connexion</a></p>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
