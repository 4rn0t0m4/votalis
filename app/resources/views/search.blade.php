<x-layouts.app title="Recherche">
    <h1 class="text-2xl font-semibold">Rechercher une proposition</h1>
    <form method="GET" action="{{ route('search') }}" role="search" class="mt-4 flex flex-wrap gap-2">
        <label for="q" class="sr-only">Mots-clés</label>
        <input id="q" name="q" type="search" value="{{ $query }}" minlength="2" maxlength="200" placeholder="Ex. cotisations des petites entreprises" class="block w-full flex-1 rounded border border-ink-300 bg-white px-3 py-2 sm:w-auto">
        <label for="theme" class="sr-only">Thème</label>
        <select id="theme" name="theme" class="rounded border border-ink-300 bg-white px-3 py-2">
            <option value="">Tous les thèmes</option>
            @foreach ($themes as $theme)
                <option value="{{ $theme->id }}" @selected($themeId === $theme->id)>{{ $theme->name }}</option>
            @endforeach
        </select>
        <x-button>Rechercher</x-button>
    </form>

    @if ($results !== null)
        <p class="mt-6 text-sm text-ink-500" aria-live="polite">{{ trans_choice(':count résultat|:count résultats', $results->total()) }} pour « {{ $query }} »</p>
        @if ($results->isNotEmpty())
            <ul class="mt-3 divide-y divide-ink-200 rounded-lg border border-ink-200 bg-white">
                @foreach ($results as $proposal)
                    <li class="p-4">
                        <h2 class="font-medium"><a href="{{ $proposal->url() }}" class="hover:underline">{{ $proposal->title }}</a></h2>
                        <p class="mt-1 line-clamp-2 text-sm text-ink-700">{{ $proposal->problem }}</p>
                        <p class="mt-1 text-xs text-ink-500">{{ $proposal->theme->fullName() }} · {{ $proposal->authorName() }} · {{ trans_choice(':count vote|:count votes', $proposal->votes_count) }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $results->links() }}</div>
        @endif
    @endif
</x-layouts.app>
