<x-layouts.app title="Proposer une mesure">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Proposer une mesure</h1>
    <p class="mt-2 max-w-2xl text-ink-700">Une fiche suit un format imposé pour que chaque mesure soit jugée sur son contenu : le problème qu'elle vise, ce qu'elle change, ce qu'elle coûte et sur quoi elle s'appuie. Votre pseudonyme sera le seul nom affiché.</p>
    <div class="mt-6">
        <livewire:proposal-form :theme="request()->integer('theme') ?: null" />
    </div>
</x-layouts.app>
