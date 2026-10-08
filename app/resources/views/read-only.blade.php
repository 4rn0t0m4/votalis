<x-layouts.app title="Lecture seule">
    <article class="max-w-2xl">
        <h1 class="text-2xl font-semibold">Plateforme en lecture seule</h1>
        <x-alert type="info" class="mt-4">{{ $message }}</x-alert>
        <p class="mt-4 text-sm text-ink-700">Votre action n'a pas été enregistrée. Vous pouvez continuer à lire les propositions, les arguments, les résultats et le journal de modération.</p>
        <p class="mt-4 text-sm"><a href="{{ url()->previous() }}" class="underline">Retour</a> · <a href="{{ route('home') }}" class="underline">Accueil</a></p>
    </article>
</x-layouts.app>
