<x-layouts.app :title="$theme->fullName()">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink-700">
        <a href="{{ route('themes.index') }}" class="hover:underline">Thèmes</a>
        @if ($theme->parent)
            <x-icon name="chevron-right" class="size-4" /><a href="{{ route('themes.show', $theme->parent) }}" class="hover:underline">{{ $theme->parent->name }}</a>
        @endif
        <x-icon name="chevron-right" class="size-4" /><span aria-current="page">{{ $theme->name }}</span>
    </nav>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            <span class="inline-flex size-14 shrink-0 items-center justify-center rounded-2xl bg-accent-600 text-white" aria-hidden="true"><x-icon :name="$theme->icon?->value ?? ($theme->parent?->icon?->value ?? 'grid')" class="size-7" /></span>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $theme->name }}</h1>
                @if ($theme->description)
                    <p class="mt-2 max-w-2xl text-lg text-ink-700">{{ $theme->description }}</p>
                @endif
            </div>
        </div>
        @if ($theme->acceptsProposals())
            @auth
                <x-button :href="route('proposals.create', ['theme' => $theme->id])"><x-icon name="pen" class="size-4" /> Proposer une mesure</x-button>
            @else
                <x-button :href="route('login')" variant="secondary">Se connecter pour proposer</x-button>
            @endauth
        @endif
    </div>

    @if ($theme->isArchived())
        <x-alert type="info" class="mt-6">Ce thème est archivé : ses propositions restent consultables, mais il n'accepte plus de contributions.</x-alert>
    @elseif (! $theme->acceptsProposals())
        <x-alert type="info" class="mt-6">Ce thème est fermé aux nouvelles propositions.</x-alert>
    @endif

    @if ($theme->children->isNotEmpty())
        <ul class="mt-6 flex flex-wrap gap-2" aria-label="Sous-thèmes">
            @foreach ($theme->children as $child)
                <li><a href="{{ route('themes.show', $child) }}" class="pill min-h-11 border-2 border-ink-200 bg-white text-sm text-ink-900 no-underline hover:border-ink-900">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <section class="mt-10" aria-labelledby="classements">
        <h2 id="classements" class="sr-only">Propositions</h2>
        <nav aria-label="Classements" class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('themes.show', [$theme, 'classement' => $key === 'recentes' ? null : $key]) }}" @if ($tab === $key) aria-current="page" @endif class="tab">{{ $label }}</a>
            @endforeach
        </nav>
        <p class="mt-3 text-sm text-ink-700">
            Aucun classement par nombre de soutiens : chaque onglet éclaire les propositions sous un angle différent.
            <a href="{{ route('how-it-works') }}" class="link">Comment fonctionne le classement</a>
        </p>

        @if ($pending)
            <x-alert type="info" class="mt-6">{{ $pending }}</x-alert>
        @elseif ($tab === 'recentes')
            @if ($proposals->isEmpty())
                <div class="mt-8 flex flex-col items-center gap-4 text-center">
                    <x-illustration name="empty" />
                    <p class="text-ink-500">Aucune proposition pour l'instant.</p>
                </div>
            @else
                <ul class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($proposals as $proposal)
                        <li class="card card-lift relative flex flex-col gap-3">
                            <div class="flex flex-wrap gap-2">
                                <x-pill tone="sand">{{ $proposal->theme->name }}</x-pill>
                                <x-pill tone="lagoon">{{ $proposal->origin->label() }}</x-pill>
                            </div>
                            <h3 class="text-xl leading-snug font-extrabold tracking-tight"><a href="{{ $proposal->url() }}" class="text-ink-900 no-underline after:absolute after:inset-0 hover:underline">{{ $proposal->title }}</a></h3>
                            <p class="line-clamp-3 text-ink-700">{{ $proposal->problem }}</p>
                            <p class="mt-auto flex flex-wrap gap-x-3 gap-y-1 text-sm text-ink-700">
                                <span>{{ $proposal->authorName() }}</span>
                                <span>{{ $proposal->created_at?->translatedFormat('j F Y') }}</span>
                                <span class="font-semibold">{{ trans_choice(':count vote|:count votes', $proposal->votes_count) }}</span>
                                <span class="font-semibold">{{ trans_choice(':count argument|:count arguments', $proposal->arguments_count) }}</span>
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $proposals->links() }}</div>
            @endif
        @else
            @if ($ranked->isEmpty())
                <div class="mt-8 flex flex-col items-center gap-4 text-center">
                    <x-illustration name="empty" />
                    <p class="text-ink-500">Pas encore assez de participation pour ce classement.@if (in_array($tab, ['clivantes', 'necessaires'], true)) Il faut au moins {{ config('votalis.rankings.min_votes') }} votes par proposition.@endif</p>
                </div>
            @else
                <ol class="mt-6 flex flex-col gap-3">
                    @foreach ($ranked as $proposal)
                        <li class="card card-lift relative flex items-start gap-4">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-ink-900 text-sm font-extrabold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                            <div class="min-w-0">
                                <h3 class="text-lg leading-snug font-extrabold tracking-tight"><span class="sr-only">{{ $loop->iteration }}. </span><a href="{{ $proposal->url() }}" class="text-ink-900 no-underline after:absolute after:inset-0 hover:underline">{{ $proposal->title }}</a></h3>
                                <p class="mt-1 text-sm text-ink-700">{{ $proposal->theme->fullName() }} · <span class="font-semibold">{{ $proposal->getAttribute('metric') }}</span></p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        @endif
    </section>
</x-layouts.app>
