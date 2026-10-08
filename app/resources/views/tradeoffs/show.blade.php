<x-layouts.app :title="$tradeoff->title">
    <nav aria-label="Fil d'Ariane" class="text-sm text-ink-500"><a href="{{ route('tradeoffs.index') }}" class="hover:underline">Arbitrages</a> › <span aria-current="page">{{ $tradeoff->title }}</span></nav>
    <h1 class="mt-2 text-2xl font-semibold">{{ $tradeoff->title }}</h1>
    <p class="mt-2 max-w-2xl text-ink-700">{{ $tradeoff->objective }}</p>
    <p class="mt-1 text-sm text-ink-500">
        Contrainte : {{ mb_strtolower($tradeoff->direction->label()) }} <strong>{{ $tradeoff->formatAmount($tradeoff->target()) }}</strong>.
        @if ($tradeoff->source_url)
            Objectif sourcé : <a href="{{ $tradeoff->source_url }}" rel="noopener nofollow" class="underline">{{ $tradeoff->source_label ?? $tradeoff->source_url }}</a>.
        @endif
        Les chiffrages sont des ordres de grandeur sourcés ; leur incertitude est indiquée pour chaque mesure.
        <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="underline">Voir les résultats</a>.
    </p>
    @if (! $tradeoff->isOpen())
        <x-alert type="info" class="mt-4">Cet arbitrage est {{ mb_strtolower($tradeoff->status->label()) }}.</x-alert>
    @endif
    <div class="mt-6">
        <livewire:tradeoff-exercise :tradeoff="$tradeoff" :key="'tradeoff-'.$tradeoff->id" />
    </div>
</x-layouts.app>
