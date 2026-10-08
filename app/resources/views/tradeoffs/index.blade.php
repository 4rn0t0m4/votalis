<x-layouts.app title="Arbitrages">
    <h1 class="text-2xl font-semibold">Arbitrages</h1>
    <p class="mt-2 max-w-2xl text-ink-700">Une mesure impopulaire seule perd toujours ; mise en concurrence avec ses alternatives, elle peut être choisie. Chaque arbitrage fixe un objectif chiffré et sourcé : composez la combinaison de mesures qui l'atteint.</p>

    @if ($tradeoffs->isEmpty())
        <p class="mt-6 text-ink-500">Aucun arbitrage ouvert pour l'instant.</p>
    @else
        <ul class="mt-6 space-y-3">
            @foreach ($tradeoffs as $tradeoff)
                <li class="rounded-lg border border-ink-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h2 class="text-lg font-semibold"><a href="{{ route('tradeoffs.show', $tradeoff) }}" class="hover:underline">{{ $tradeoff->title }}</a></h2>
                        <span class="rounded bg-ink-100 px-2 py-0.5 text-xs">{{ $tradeoff->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-ink-700">{{ $tradeoff->objective }}</p>
                    <p class="mt-1 text-xs text-ink-500">
                        {{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($tradeoff->target()) }}
                        · {{ trans_choice(':count mesure|:count mesures', $tradeoff->items_count) }}
                        · {{ trans_choice(':count participant|:count participants', $tradeoff->answers_count) }}
                        @if ($tradeoff->theme) · {{ $tradeoff->theme->name }} @endif
                        · <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="underline">résultats</a>
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
