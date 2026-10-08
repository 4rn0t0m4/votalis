<x-layouts.app title="Mon compte">
    <h1 class="text-2xl font-semibold">Mon compte</h1>
    <dl class="mt-6 grid max-w-md gap-3 text-sm">
        <div><dt class="text-ink-500">Pseudonyme</dt><dd class="font-medium">{{ auth()->user()->pseudonym }}</dd></div>
        <div><dt class="text-ink-500">Rôle</dt><dd class="font-medium">{{ auth()->user()->role->label() }}</dd></div>
        <div><dt class="text-ink-500">Membre depuis</dt><dd class="font-medium">{{ auth()->user()->created_at?->translatedFormat('j F Y') }}</dd></div>
    </dl>
    @if (auth()->user()->isSuspended())
        <x-alert type="error" class="mt-6 max-w-md">Votre compte est suspendu @if (auth()->user()->suspended_until) jusqu'au {{ auth()->user()->suspended_until->translatedFormat('j F Y') }}@endif : vous pouvez lire et contester, mais plus contribuer. <a href="{{ route('account.moderation.index') }}" class="underline">Voir la décision</a>.</x-alert>
    @endif
    <ul class="mt-6 space-y-2 text-sm">
        <li><a href="{{ route('security.show') }}" class="underline">Sécurité du compte : double authentification et clés d'accès</a></li>
        <li><a href="{{ route('account.moderation.index') }}" class="underline">Modération : décisions concernant mes contributions et contestations</a></li>
    </ul>
</x-layouts.app>
