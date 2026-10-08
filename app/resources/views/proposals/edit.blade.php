<x-layouts.app :title="'Modifier : '.$proposal->title">
    <h1 class="text-2xl font-semibold">{{ $proposal->isLocked() ? 'Corriger la forme' : 'Modifier la proposition' }}</h1>
    <p class="mt-2 text-sm text-ink-500">Chaque modification est conservée dans l'historique public de la fiche.</p>
    <div class="mt-6">
        <livewire:proposal-form :proposal="$proposal" />
    </div>
</x-layouts.app>
