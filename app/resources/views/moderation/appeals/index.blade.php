<x-layouts.app title="Contestations">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Contestations</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Le comité éditorial tranche chaque contestation. Vous ne pouvez pas traiter celle d'une décision que vous avez prise vous-même : elle est signalée ci-dessous et attend un autre membre.</p>

    <h2 class="mt-6 text-lg font-semibold">En attente ({{ $appeals->count() }})</h2>
    <table class="mt-3 w-full text-sm">
        <thead class="text-left text-ink-700"><tr><th class="py-2">Déposée le</th><th class="py-2">Décision contestée</th><th class="py-2">Contenu</th><th class="py-2"><span class="sr-only">Action</span></th></tr></thead>
        <tbody class="divide-y divide-ink-200">
            @forelse ($appeals as $appeal)
                @php($entry = $appeal->logEntry)
                <tr>
                    <td class="py-2 whitespace-nowrap">{{ $appeal->created_at?->translatedFormat('j F Y') }}</td>
                    <td class="py-2">{{ $entry->action->label() }}@if ($entry->motive) · {{ $entry->motive->label() }}@endif</td>
                    <td class="py-2">
                        @if ($entry->target_type === 'user') Compte participant
                        @elseif ($entry->target instanceof \App\Models\Proposal) {{ $entry->target->title }}
                        @elseif ($entry->target instanceof \App\Models\Argument) Argument : {{ Str::limit($entry->target->body, 80) }}
                        @else Contenu supprimé @endif
                    </td>
                    <td class="py-2 text-right">
                        @if ($entry->actor_id === $user->id)
                            <span class="text-xs text-ink-700">Votre décision : un autre membre tranche</span>
                        @else
                            <a href="{{ route('moderation.appeals.show', $appeal) }}" class="link">Examiner</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-ink-500">Aucune contestation en attente.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($decided->isNotEmpty())
        <h2 class="mt-8 text-lg font-semibold">Dernières contestations tranchées</h2>
        <ul class="mt-3 space-y-1 text-sm text-ink-700">
            @foreach ($decided as $appeal)
                <li>{{ $appeal->decided_at?->translatedFormat('j F Y') }} · {{ $appeal->logEntry->action->label() }} · {{ $appeal->status->label() }}</li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
