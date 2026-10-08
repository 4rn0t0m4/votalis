<x-layouts.app title="Signaux d'intégrité">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Signaux d'intégrité</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Calculés chaque nuit sur les 24 dernières heures. Un signal n'entraîne jamais d'action automatique : il appelle un examen humain, puis une décision de modération ordinaire, journalisée. Les seuils relèvent de la configuration privée. Les cibles sont des identifiants internes.</p>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 text-sm">
        <div>
            <label for="statut" class="mb-1.5 block font-bold">Statut</label>
            <select id="statut" name="statut" class="field">
                <option value="">Tous</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-button variant="secondary">Filtrer</x-button>
    </form>

    <table class="mt-6 w-full text-sm">
        <thead class="text-left text-ink-700"><tr><th class="py-2">Date</th><th class="py-2">Signal</th><th class="py-2">Gravité</th><th class="py-2">Cibles</th><th class="py-2">Détails</th><th class="py-2">Statut</th></tr></thead>
        <tbody class="divide-y divide-ink-200">
            @forelse ($signals as $signal)
                <tr>
                    <td class="py-2 whitespace-nowrap">{{ $signal->window_date->translatedFormat('j F Y') }}</td>
                    <td class="py-2">{{ $signal->type->label() }}</td>
                    <td class="py-2">{{ ['', 'faible', 'moyenne', 'forte'][$signal->severity] ?? $signal->severity }}</td>
                    <td class="py-2">
                        @if (isset($signal->targets['proposal_id']))
                            <a href="{{ route('proposals.show', ['proposal' => $signal->targets['proposal_id']]) }}" class="link">proposition n°{{ $signal->targets['proposal_id'] }}</a>
                        @elseif (isset($signal->targets['proposal_ids']))
                            @foreach ($signal->targets['proposal_ids'] as $id)
                                <a href="{{ route('proposals.show', ['proposal' => $id]) }}" class="link">n°{{ $id }}</a>{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        @elseif (isset($signal->targets['user_ids']))
                            {{ count($signal->targets['user_ids']) }} comptes (identifiants {{ implode(', ', $signal->targets['user_ids']) }})
                        @else
                            {{ $signal->targets['scope'] ?? '—' }}
                        @endif
                    </td>
                    <td class="py-2 text-xs text-ink-700">
                        @foreach ($signal->details ?? [] as $k => $v)
                            {{ $k }} : {{ is_array($v) ? json_encode($v) : $v }}{{ $loop->last ? '' : ' · ' }}
                        @endforeach
                    </td>
                    <td class="py-2">
                        @if ($canReview)
                            <form method="POST" action="{{ route('moderation.signals.update', $signal) }}" class="flex items-center gap-2">
                                @csrf
                                <label for="status-{{ $signal->id }}" class="sr-only">Statut du signal {{ $signal->id }}</label>
                                <select id="status-{{ $signal->id }}" name="status" class="rounded border border-ink-300 px-2 py-1 text-xs">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s->value }}" @selected($signal->status === $s)>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded border border-ink-300 px-2 py-1 text-xs hover:bg-ink-100">OK</button>
                            </form>
                        @else
                            {{ $signal->status->label() }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4 text-ink-500">Aucun signal.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $signals->links() }}</div>
</x-layouts.app>
