<x-layouts.app title="Contenu masqué">
    <article class="max-w-2xl">
        <h1 class="text-2xl font-semibold">Contenu masqué</h1>
        <x-alert type="info" class="mt-4">
            @if ($proposal->hidesEverything())
                Cette proposition a été masquée par la modération pour contenu illégal. Son texte n'est pas reproduit.
            @elseif ($proposal->awaitsRewrite())
                La proposition « {{ $proposal->title }} » est en attente de reformulation par son auteur, à la demande de la modération @if ($proposal->hidden_motive) ({{ $proposal->hidden_motive->label() }})@endif.
            @else
                La proposition « {{ $proposal->title }} » a été masquée par la modération @if ($proposal->hidden_motive) pour le motif suivant : {{ $proposal->hidden_motive->label() }}@endif.
            @endif
        </x-alert>
        @if ($entry && $entry->showsTarget() && isset($entry->details['kept_proposal_id']))
            @php($kept = \App\Models\Proposal::query()->published()->find($entry->details['kept_proposal_id']))
            @if ($kept)
                <p class="mt-4 text-sm">Fiche conservée : <a href="{{ $kept->url() }}" class="underline">{{ $kept->title }}</a>.</p>
            @endif
        @endif
        <p class="mt-4 text-sm text-ink-700">
            @if ($entry)
                La décision figure au <a href="{{ $entry->url() }}" class="underline">journal public de modération</a>.
            @else
                Les décisions figurent au <a href="{{ route('moderation-log.index') }}" class="underline">journal public de modération</a>.
            @endif
            Elle a été prise selon la <a href="{{ route('charter') }}" class="underline">charte de modération</a> et peut être contestée par l'auteur.
        </p>
        <p class="mt-6 text-sm"><a href="{{ route('themes.show', $proposal->theme) }}" class="underline">Retour au thème</a></p>
    </article>
</x-layouts.app>
