<x-layouts.app>
    <section class="max-w-2xl">
        <h1 class="text-3xl font-semibold">Des mesures concrètes, à débattre et à arbitrer ensemble.</h1>
        <p class="mt-4 text-lg text-ink-700">
            Cette plateforme recense des mesures pour la France, présentées avec leur problème, leur coût estimé,
            leurs sources et leurs arguments pour et contre. Chacun peut les voter, les débattre, en proposer,
            et surtout arbitrer entre elles sous contrainte.
        </p>
        <p class="mt-4 text-ink-700">
            Indépendante et non partisane : aucune étiquette politique, pseudonymat par défaut, modération publique,
            code source libre.
        </p>
        <div class="mt-6 flex gap-3">
            @guest
                <a href="{{ route('register') }}" class="rounded bg-accent-600 px-4 py-2 text-sm font-medium text-white hover:bg-accent-700">Créer un compte</a>
            @endguest
            <a href="{{ route('themes.index') }}" class="rounded border border-ink-300 bg-white px-4 py-2 text-sm font-medium hover:bg-ink-100">Parcourir les thèmes</a>
            <a href="{{ route('how-it-works') }}" class="rounded border border-ink-300 bg-white px-4 py-2 text-sm font-medium hover:bg-ink-100">Comment ça marche</a>
        </div>
    </section>
</x-layouts.app>
