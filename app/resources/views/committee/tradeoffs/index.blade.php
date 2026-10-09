<x-layouts.app title="Arbitrages · Comité éditorial">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Arbitrages</h1>
        <a href="{{ route('committee.tradeoffs.create') }}" class="btn btn-primary">Nouvel arbitrage</a>
    </div>
    <table class="mt-6 w-full text-sm">
        <thead class="text-left text-ink-700"><tr><th class="py-2">Titre</th><th class="py-2">Statut</th><th class="py-2">Mesures</th><th class="py-2">Participants</th><th class="py-2">Suggestions</th><th class="py-2"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody class="divide-y divide-ink-200">
            @forelse ($tradeoffs as $tradeoff)
                <tr>
                    <td class="py-2 font-medium">{{ $tradeoff->title }}</td>
                    <td class="py-2">{{ $tradeoff->status->label() }}</td>
                    <td class="py-2">{{ $tradeoff->items_count }}</td>
                    <td class="py-2">{{ $tradeoff->answers_count }}</td>
                    <td class="py-2">{{ $tradeoff->suggestions_count }}</td>
                    <td class="py-2 text-right"><a href="{{ route('committee.tradeoffs.edit', $tradeoff) }}" class="link">Modifier</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4 text-ink-500">Aucun arbitrage.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layouts.app>
