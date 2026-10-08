<x-layouts.app title="Journal de modération">
    <h1 class="text-2xl font-semibold">Journal public de modération</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Chaque décision de modération est inscrite ici, avec sa date, le type de contenu, l'action et le motif. Les entrées ne peuvent être ni modifiées ni supprimées. Le contenu masqué pour motif illégal n'est jamais reproduit. Les décisions sont prises selon la <a href="{{ route('charter') }}" class="underline">charte de modération</a> et peuvent être contestées par l'auteur.</p>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 text-sm">
        <div>
            <label for="type" class="mb-1 block font-medium">Type de contenu</label>
            <select id="type" name="type" class="rounded border border-ink-300 px-3 py-2">
                <option value="">Tous</option>
                <option value="proposal" @selected($type === 'proposal')>Propositions</option>
                <option value="argument" @selected($type === 'argument')>Arguments</option>
            </select>
        </div>
        <div>
            <label for="action" class="mb-1 block font-medium">Action</label>
            <select id="action" name="action" class="rounded border border-ink-300 px-3 py-2">
                <option value="">Toutes</option>
                @foreach ($actions as $a)
                    <option value="{{ $a->value }}" @selected($action === $a)>{{ $a->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-button variant="secondary">Filtrer</x-button>
    </form>

    <table class="mt-6 w-full text-sm">
        <caption class="sr-only">Entrées du journal, de la plus récente à la plus ancienne</caption>
        <thead class="text-left text-ink-500"><tr><th class="py-2">Date</th><th class="py-2">Contenu</th><th class="py-2">Action</th><th class="py-2">Motif</th><th class="py-2">Par</th></tr></thead>
        <tbody class="divide-y divide-ink-200">
            @forelse ($entries as $entry)
                <tr>
                    <td class="py-2 whitespace-nowrap"><a href="{{ $entry->url() }}" class="underline">{{ $entry->created_at->translatedFormat('j F Y à H\hi') }}</a></td>
                    <td class="py-2">
                        {{ $entry->targetLabel() }}
                        @include('moderation-log.partials.target-link', ['entry' => $entry])
                    </td>
                    <td class="py-2">{{ $entry->action->label() }}</td>
                    <td class="py-2">{{ $entry->motive?->label() ?? '—' }}</td>
                    <td class="py-2">{{ $entry->actor_role->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-ink-500">Aucune décision pour l'instant.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $entries->links() }}</div>
</x-layouts.app>
