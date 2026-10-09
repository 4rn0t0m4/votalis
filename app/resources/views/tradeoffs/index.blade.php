<x-layouts.app title="Arbitrages">
    <div class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Arbitrages</h1>
        <p class="mt-3 text-lg text-ink-700">Une mesure impopulaire seule perd toujours ; mise en concurrence avec ses alternatives, elle peut être choisie. Chaque arbitrage fixe un objectif chiffré et sourcé : composez la combinaison de mesures qui l'atteint.</p>
    </div>

    @if ($tradeoffs->isEmpty())
        <div class="mt-10 flex flex-col items-center gap-4 text-center">
            <x-illustration name="empty" />
            <p class="text-ink-500">Aucun arbitrage ouvert pour l'instant.</p>
        </div>
    @else
        <ul class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach ($tradeoffs as $tradeoff)
                <li class="{{ $tradeoff->isOpen() ? 'card-plum' : 'card-flat' }} card-lift rise relative flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-pill :tone="$tradeoff->isOpen() ? 'ink' : 'outline'">{{ $tradeoff->status->label() }}</x-pill>
                        @if ($tradeoff->theme) <x-pill tone="sand">{{ $tradeoff->theme->name }}</x-pill> @endif
                    </div>
                    <h2 class="text-2xl leading-tight font-extrabold tracking-tight"><a href="{{ route('tradeoffs.show', $tradeoff) }}" class="text-ink-900 no-underline after:absolute after:inset-0 hover:underline">{{ $tradeoff->title }}</a></h2>
                    <p class="text-ink-700">{{ $tradeoff->objective }}</p>
                    <p class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-700">
                        <span class="text-lg font-extrabold text-ink-900">{{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($tradeoff->target()) }}</span>
                        <span>{{ trans_choice(':count mesure|:count mesures', $tradeoff->items_count) }}</span>
                        <span>{{ trans_choice(':count participant|:count participants', $tradeoff->answers_count) }}</span>
                        <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="link relative z-10">résultats</a>
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
