<x-layouts.app title="Modération de mes contributions">
    <p class="text-sm"><a href="{{ route('account.show') }}" class="link">← Mon compte</a></p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Décisions de modération me concernant</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Chaque décision est motivée par l'un des motifs de la <a href="{{ route('charter') }}" class="link">charte</a> et inscrite au <a href="{{ route('moderation-log.index') }}" class="link">journal public</a>. Vous pouvez la contester une fois, dans les {{ config('votalis.moderation.appeal_days') }} jours ; le comité éditorial tranche, jamais l'auteur de la décision.</p>

    @if ($entries->isEmpty())
        <p class="mt-6 text-sm text-ink-700">Aucune décision ne concerne vos contributions.</p>
    @else
        <ul class="mt-6 divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white">
            @foreach ($entries as $entry)
                @php($target = $entry->target)
                <li class="p-4 text-sm">
                    <p>
                        <span class="font-medium">{{ $entry->action->label() }}</span>
                        @if ($entry->motive) · {{ $entry->motive->label() }} @endif
                        · {{ $entry->created_at->translatedFormat('j F Y à H\hi') }}
                        · <a href="{{ $entry->url() }}" class="link">journal</a>
                    </p>
                    <p class="mt-1 text-ink-700">
                        @if ($entry->target_type === 'user')
                            Votre compte @if (isset($entry->details['days'])) , pour {{ $entry->details['days'] }} jours @endif
                        @elseif ($target instanceof \App\Models\Proposal)
                            Proposition « <a href="{{ $target->url() }}" class="link">{{ $target->title }}</a> »
                        @elseif ($target instanceof \App\Models\Argument)
                            Argument « {{ Str::limit($target->body, 120) }} »
                        @else
                            Contenu supprimé
                        @endif
                    </p>
                    @if ($entry->appeal)
                        <p class="mt-2 rounded bg-ink-50 px-3 py-2 text-ink-700">
                            Contestation du {{ $entry->appeal->created_at?->translatedFormat('j F Y') }} : <strong>{{ $entry->appeal->status->label() }}</strong>
                            @if ($entry->appeal->decision_note) <br>Motivation du comité : « {{ $entry->appeal->decision_note }} » @endif
                        </p>
                    @elseif ($user->can('appeal', $entry))
                        <p class="mt-2"><a href="{{ route('account.moderation.appeal', $entry) }}" class="link">Contester cette décision</a> (jusqu'au {{ $entry->appealDeadline()->translatedFormat('j F Y') }})</p>
                    @elseif ($entry->action->isAppealable())
                        <p class="mt-2 text-xs text-ink-700">Délai de contestation dépassé.</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
</x-layouts.app>
