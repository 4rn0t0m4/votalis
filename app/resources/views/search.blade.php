<x-layouts.app title="Recherche">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Rechercher une proposition</h1>
    <form method="GET" action="{{ route('search') }}" role="search" class="card mt-6 flex flex-wrap gap-3">
        <label for="q" class="sr-only">Mots-clés</label>
        <input id="q" name="q" type="search" value="{{ $query }}" minlength="2" maxlength="200" placeholder="Ex. cotisations des petites entreprises" class="field min-w-0 flex-1 sm:w-auto">
        <label for="theme" class="sr-only">Thème</label>
        <select id="theme" name="theme" class="field w-full sm:w-auto">
            <option value="">Tous les thèmes</option>
            @foreach ($themes as $theme)
                <option value="{{ $theme->id }}" @selected($themeId === $theme->id)>{{ $theme->name }}</option>
            @endforeach
        </select>
        <x-button class="w-full sm:w-auto"><x-icon name="search" class="size-4" /> Rechercher</x-button>
    </form>

    @if ($results !== null)
        <p class="mt-6 text-sm font-semibold text-ink-700" aria-live="polite">{{ trans_choice(':count résultat|:count résultats', $results->total()) }} pour « {{ $query }} »</p>
        @if ($results->isNotEmpty())
            <ul class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($results as $proposal)
                    <li class="card card-lift relative flex flex-col gap-2">
                        <x-pill tone="sand" class="self-start">{{ $proposal->theme->fullName() }}</x-pill>
                        <h2 class="text-xl leading-snug font-extrabold tracking-tight"><a href="{{ $proposal->url() }}" class="text-ink-900 no-underline after:absolute after:inset-0 hover:underline">{{ $proposal->title }}</a></h2>
                        <p class="line-clamp-2 text-ink-700">{{ $proposal->problem }}</p>
                        <p class="mt-auto text-sm text-ink-700">{{ $proposal->authorName() }} · {{ trans_choice(':count vote|:count votes', $proposal->votes_count) }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $results->links() }}</div>
        @endif
    @endif
</x-layouts.app>
