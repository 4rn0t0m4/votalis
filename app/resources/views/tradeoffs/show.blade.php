<x-layouts.app :title="$tradeoff->title">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink-700"><a href="{{ route('tradeoffs.index') }}" class="hover:underline">Arbitrages</a> <x-icon name="chevron-right" class="size-4" /> <span aria-current="page">{{ $tradeoff->title }}</span></nav>
    <div class="mt-4 max-w-3xl">
        <h1 class="text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl">{{ $tradeoff->title }}</h1>
        <p class="mt-3 text-lg text-ink-700">{{ $tradeoff->objective }}</p>
        <p class="mt-3 text-sm text-ink-700">
            Contrainte : {{ mb_strtolower($tradeoff->direction->label()) }} <strong class="text-ink-900">{{ $tradeoff->formatAmount($tradeoff->target()) }}</strong>.
            @if ($tradeoff->source_url)
                Objectif sourcé : <a href="{{ $tradeoff->source_url }}" rel="noopener nofollow" class="link">{{ $tradeoff->source_label ?? $tradeoff->source_url }}</a>.
            @endif
            Les chiffrages sont des ordres de grandeur sourcés ; leur incertitude est indiquée pour chaque mesure.
            <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="link">Voir les résultats</a>.
        </p>
    </div>
    @if (! $tradeoff->isOpen())
        <x-alert type="info" class="mt-5">Cet arbitrage est {{ mb_strtolower($tradeoff->status->label()) }}.</x-alert>
    @endif
    <div class="mt-6">
        <livewire:tradeoff-exercise :tradeoff="$tradeoff" :key="'tradeoff-'.$tradeoff->id" />
    </div>
</x-layouts.app>
