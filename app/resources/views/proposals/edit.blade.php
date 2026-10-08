<x-layouts.app :title="'Modifier : '.$proposal->title">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $proposal->isLocked() ? 'Corriger la forme' : 'Modifier la proposition' }}</h1>
    <p class="mt-2 text-sm text-ink-700">Chaque modification est conservée dans l'historique public de la fiche.</p>
    <div class="mt-6">
        <livewire:proposal-form :proposal="$proposal" />
    </div>
</x-layouts.app>
