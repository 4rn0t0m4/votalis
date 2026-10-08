<x-layouts.app title="File de modération">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">File de modération</h1>
        <p class="text-sm text-ink-700">{{ $openCount }} {{ Str::plural('dossier', $openCount) }} en attente · <a href="{{ route('moderation-log.index') }}" class="link">Journal public</a> · <a href="{{ route('charter') }}" class="link">Charte</a></p>
    </div>

    <table class="mt-6 w-full text-sm">
        <caption class="sr-only">Dossiers triés par gravité puis par ancienneté</caption>
        <thead class="text-left text-ink-700"><tr><th class="py-2">Gravité</th><th class="py-2">Contenu</th><th class="py-2">Signalements</th><th class="py-2">Premier signalement</th><th class="py-2"><span class="sr-only">Action</span></th></tr></thead>
        <tbody class="divide-y divide-ink-200">
            @forelse ($cases as $case)
                @php($target = $case['target'])
                <tr>
                    <td class="py-2">
                        <span class="rounded px-2 py-0.5 text-xs font-medium {{ $case['severity'] === 3 ? 'bg-red-50 text-red-900' : ($case['severity'] === 2 ? 'bg-ink-200 text-ink-900' : 'bg-ink-100 text-ink-700') }}">
                            {{ ['', 'ordinaire', 'prioritaire', 'urgente'][$case['severity']] }}
                        </span>
                        @if ($target->isModerated()) <span class="ml-1 text-xs text-ink-700">(masqué)</span> @endif
                    </td>
                    <td class="py-2">
                        @if ($target instanceof \App\Models\Proposal)
                            <span class="text-xs uppercase text-ink-500">Proposition</span><br>{{ $target->title }}
                        @else
                            <span class="text-xs uppercase text-ink-500">Argument {{ $target->side->label() }}</span><br>{{ Str::limit($target->body, 120) }}
                        @endif
                    </td>
                    <td class="py-2">{{ $case['count'] }}</td>
                    <td class="py-2">{{ $case['oldest']->translatedFormat('j F Y à H\hi') }}</td>
                    <td class="py-2 text-right"><a href="{{ route('moderation.case', ['type' => \App\Support\ModerationTarget::slugFor($target), 'id' => $target->id]) }}" class="link">Traiter</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-ink-500">Aucun signalement en attente.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-layouts.app>
