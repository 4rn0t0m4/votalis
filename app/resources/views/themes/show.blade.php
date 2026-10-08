<x-layouts.app :title="$theme->fullName()">
    <nav aria-label="Fil d'Ariane" class="text-sm text-ink-500">
        <a href="{{ route('themes.index') }}" class="hover:underline">Thèmes</a>
        @if ($theme->parent)
            › <a href="{{ route('themes.show', $theme->parent) }}" class="hover:underline">{{ $theme->parent->name }}</a>
        @endif
        › <span aria-current="page">{{ $theme->name }}</span>
    </nav>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $theme->name }}</h1>
            @if ($theme->description)
                <p class="mt-2 max-w-2xl text-ink-700">{{ $theme->description }}</p>
            @endif
        </div>
        @if ($theme->acceptsProposals())
            @auth
                <a href="{{ route('proposals.create', ['theme' => $theme->id]) }}" class="rounded bg-accent-600 px-4 py-2 text-sm font-medium text-white hover:bg-accent-700">Proposer une mesure</a>
            @else
                <a href="{{ route('login') }}" class="rounded border border-ink-300 bg-white px-4 py-2 text-sm font-medium hover:bg-ink-100">Se connecter pour proposer</a>
            @endauth
        @endif
    </div>

    @if ($theme->isArchived())
        <x-alert type="info" class="mt-4">Ce thème est archivé : ses propositions restent consultables, mais il n'accepte plus de contributions.</x-alert>
    @elseif (! $theme->acceptsProposals())
        <x-alert type="info" class="mt-4">Ce thème est fermé aux nouvelles propositions.</x-alert>
    @endif

    @if ($theme->children->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2 text-sm" aria-label="Sous-thèmes">
            @foreach ($theme->children as $child)
                <li><a href="{{ route('themes.show', $child) }}" class="rounded border border-ink-300 bg-white px-2 py-0.5 hover:bg-ink-100">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <section class="mt-8" aria-labelledby="classements">
        <h2 id="classements" class="sr-only">Propositions</h2>
        <nav aria-label="Classements" class="flex flex-wrap gap-2 border-b border-ink-200 pb-3 text-sm">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('themes.show', [$theme, 'classement' => $key === 'recentes' ? null : $key]) }}"
                    @if ($tab === $key) aria-current="page" @endif
                    class="rounded px-3 py-1 {{ $tab === $key ? 'bg-ink-900 text-white' : 'border border-ink-300 bg-white hover:bg-ink-100' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <p class="mt-3 text-sm text-ink-500">
            Aucun classement par nombre de soutiens : chaque onglet éclaire les propositions sous un angle différent.
            <a href="{{ route('how-it-works') }}" class="underline">Comment fonctionne le classement</a>
        </p>

        @if ($pending)
            <x-alert type="info" class="mt-4">{{ $pending }}</x-alert>
        @elseif ($tab === 'recentes')
            @if ($proposals->isEmpty())
                <p class="mt-4 text-ink-500">Aucune proposition pour l'instant.</p>
            @else
                <ul class="mt-4 divide-y divide-ink-200 rounded-lg border border-ink-200 bg-white">
                    @foreach ($proposals as $proposal)
                        <li class="p-4">
                            <h3 class="font-medium"><a href="{{ $proposal->url() }}" class="hover:underline">{{ $proposal->title }}</a></h3>
                            <p class="mt-1 line-clamp-2 text-sm text-ink-700">{{ $proposal->problem }}</p>
                            <p class="mt-1 text-xs text-ink-500">
                                {{ $proposal->theme->fullName() }} · {{ $proposal->authorName() }} · {{ $proposal->created_at?->translatedFormat('j F Y') }}
                                · {{ trans_choice(':count vote|:count votes', $proposal->votes_count) }}
                                · {{ trans_choice(':count argument|:count arguments', $proposal->arguments_count) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $proposals->links() }}</div>
            @endif
        @else
            @if ($ranked->isEmpty())
                <p class="mt-4 text-ink-500">Pas encore assez de votes pour ce classement. Il faut au moins {{ config('votalis.rankings.min_votes') }} votes par proposition.</p>
            @else
                <ol class="mt-4 divide-y divide-ink-200 rounded-lg border border-ink-200 bg-white">
                    @foreach ($ranked as $proposal)
                        <li class="flex gap-3 p-4">
                            <span class="text-sm text-ink-500">{{ $loop->iteration }}.</span>
                            <div>
                                <h3 class="font-medium"><a href="{{ $proposal->url() }}" class="hover:underline">{{ $proposal->title }}</a></h3>
                                <p class="mt-1 text-xs text-ink-500">{{ $proposal->theme->fullName() }} · {{ $proposal->getAttribute('metric') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        @endif
    </section>
</x-layouts.app>
