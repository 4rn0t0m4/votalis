<x-layouts.app title="Entrée du journal de modération">
    <p class="text-sm"><a href="{{ route('moderation-log.index') }}" class="link">← Journal de modération</a></p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Décision n°{{ $entry->id }}</h1>
    <dl class="mt-6 max-w-xl divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white text-sm">
        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Date</dt><dd>{{ $entry->created_at->translatedFormat('j F Y à H\hi') }}</dd></div>
        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Contenu</dt><dd>{{ $entry->targetLabel() }} @include('moderation-log.partials.target-link', ['entry' => $entry])</dd></div>
        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Action</dt><dd>{{ $entry->action->label() }}</dd></div>
        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Motif</dt><dd>{{ $entry->motive?->label() ?? '—' }}</dd></div>
        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Décidé par</dt><dd>{{ $entry->actor_role->label() }}</dd></div>
        @if (isset($entry->details['kept_proposal_id']))
            @php($kept = \App\Models\Proposal::query()->published()->find($entry->details['kept_proposal_id']))
            @if ($kept)
                <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-ink-500">Fiche conservée</dt><dd><a href="{{ $kept->url() }}" class="link">{{ $kept->title }}</a></dd></div>
            @endif
        @endif
    </dl>
    <p class="mt-4 max-w-xl text-sm text-ink-700">Cette entrée est immuable. L'auteur du contenu peut contester la décision une fois ; la contestation est tranchée par le comité éditorial, jamais par l'auteur de la décision. Voir la <a href="{{ route('charter') }}" class="link">charte de modération</a>.</p>
</x-layouts.app>
