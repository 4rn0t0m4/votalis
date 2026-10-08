<x-layouts.app :title="'Résultats : '.$tradeoff->title">
    <nav aria-label="Fil d'Ariane" class="text-sm text-ink-500"><a href="{{ route('tradeoffs.index') }}" class="hover:underline">Arbitrages</a> › <a href="{{ route('tradeoffs.show', $tradeoff) }}" class="hover:underline">{{ $tradeoff->title }}</a> › <span aria-current="page">Résultats</span></nav>
    <h1 class="mt-2 text-2xl font-semibold">Résultats : {{ $tradeoff->title }}</h1>
    <p class="mt-2 text-sm text-ink-500">{{ trans_choice(':count participant|:count participants', $results['participants']) }} · {{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($tradeoff->target()) }}</p>

    <section class="mt-6" aria-labelledby="frequence">
        <h2 id="frequence" class="text-lg font-semibold">Fréquence de choix de chaque mesure</h2>
        @if ($results['participants'] === 0)
            <p class="mt-2 text-ink-500">Personne n'a encore répondu.</p>
        @else
            <ol class="mt-3 divide-y divide-ink-200 rounded-lg border border-ink-200 bg-white">
                @foreach ($results['items'] as $row)
                    <li class="p-4">
                        <div class="flex items-baseline justify-between gap-3">
                            <a href="{{ $row['item']->proposal->url() }}" class="font-medium hover:underline">{{ $row['item']->proposal->title }}</a>
                            <span class="text-sm"><strong>{{ $row['percent'] }} %</strong> <span class="text-ink-500">({{ $row['count'] }})</span></span>
                        </div>
                        <div class="mt-1 h-2 w-full rounded bg-ink-100"><div class="h-2 rounded bg-accent-600 w-[{{ $row['percent'] }}%]"></div></div>
                        <p class="mt-1 text-xs text-ink-500">{{ $tradeoff->formatAmount($row['item']->impactValue()) }} · incertitude : {{ $row['item']->uncertainty }}</p>
                        @if ($row['conditions'] !== [])
                            <p class="mt-1 text-xs text-ink-700">Conditions les plus citées :
                                @foreach ($row['conditions'] as $condition)
                                    « {{ $condition['text'] }} » ({{ $condition['count'] }}){{ $loop->last ? '' : ' · ' }}
                                @endforeach
                            </p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    @if ($results['combinations'] !== [])
        <section class="mt-8" aria-labelledby="combinaisons">
            <h2 id="combinaisons" class="text-lg font-semibold">Combinaisons les plus fréquentes</h2>
            @php $titles = collect($results['items'])->mapWithKeys(fn ($r) => [$r['item']->id => $r['item']->proposal->title]); @endphp
            <ol class="mt-3 space-y-2">
                @foreach ($results['combinations'] as $combination)
                    <li class="rounded-lg border border-ink-200 bg-white p-4 text-sm">
                        <p class="font-medium">{{ $combination['percent'] }} % des participants · {{ $tradeoff->formatAmount($combination['total']) }}</p>
                        <ul class="mt-1 list-disc pl-5 text-ink-700">
                            @foreach ($combination['item_ids'] as $id)
                                <li>{{ $titles[$id] ?? 'Mesure retirée' }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
    <p class="mt-6 text-xs text-ink-500">La ventilation par famille de votants arrivera en V2.</p>
</x-layouts.app>
