<x-layouts.app title="Thèmes · Comité éditorial">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Thèmes</h1>
        <a href="{{ route('committee.themes.create') }}" class="rounded bg-accent-600 px-4 py-2 text-sm font-medium text-white hover:bg-accent-700">Nouveau thème</a>
    </div>
    <p class="mt-2 text-sm text-ink-500">Deux niveaux au maximum. Un thème ne se supprime pas : il s'archive.</p>

    <table class="mt-6 w-full text-sm">
        <thead class="text-left text-ink-500">
            <tr><th class="py-2">Thème</th><th class="py-2">Statut</th><th class="py-2">Ordre</th><th class="py-2">Propositions</th><th class="py-2"><span class="sr-only">Actions</span></th></tr>
        </thead>
        <tbody class="divide-y divide-ink-200">
            @foreach ($themes as $theme)
                <tr>
                    <td class="py-2 font-medium">{{ $theme->name }}</td>
                    <td class="py-2">{{ $theme->status->label() }}</td>
                    <td class="py-2">{{ $theme->position }}</td>
                    <td class="py-2">{{ $theme->proposals_count }}</td>
                    <td class="py-2 text-right"><a href="{{ route('committee.themes.edit', $theme) }}" class="underline">Modifier</a></td>
                </tr>
                @foreach ($theme->children as $child)
                    <tr>
                        <td class="py-2 pl-6">› {{ $child->name }}</td>
                        <td class="py-2">{{ $child->status->label() }}</td>
                        <td class="py-2">{{ $child->position }}</td>
                        <td class="py-2">{{ $child->proposals()->count() }}</td>
                        <td class="py-2 text-right"><a href="{{ route('committee.themes.edit', $child) }}" class="underline">Modifier</a></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</x-layouts.app>
