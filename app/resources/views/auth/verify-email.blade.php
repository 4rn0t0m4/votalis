<x-layouts.app title="Vérifier votre adresse e-mail">
    <x-auth-card title="Vérifiez votre adresse e-mail">
        <p class="mb-4 text-sm text-ink-700">
            Un lien de vérification vous a été envoyé. Cliquez dessus pour activer votre compte.
            Il est valable 60 minutes.
        </p>
        @if (session('status') === 'verification-link-sent')
            <x-alert type="success" class="mb-4">Un nouveau lien vient d'être envoyé.</x-alert>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button variant="secondary" class="w-full">Renvoyer le lien</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
