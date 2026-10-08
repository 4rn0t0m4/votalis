<x-layouts.app title="Mon compte">
    <h1 class="text-2xl font-semibold">Mon compte</h1>
    <dl class="mt-6 grid max-w-md gap-3 text-sm">
        <div><dt class="text-ink-500">Pseudonyme</dt><dd class="font-medium">{{ auth()->user()->pseudonym }}</dd></div>
        <div><dt class="text-ink-500">Rôle</dt><dd class="font-medium">{{ auth()->user()->role->label() }}</dd></div>
        <div><dt class="text-ink-500">Membre depuis</dt><dd class="font-medium">{{ auth()->user()->created_at?->translatedFormat('j F Y') }}</dd></div>
    </dl>
    <p class="mt-6"><a href="{{ route('security.show') }}" class="underline">Sécurité du compte : double authentification et clés d'accès</a></p>
</x-layouts.app>
