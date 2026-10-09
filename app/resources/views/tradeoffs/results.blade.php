<x-layouts.app :title="'Résultats : '.$tradeoff->title">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink-700"><a href="{{ route('tradeoffs.index') }}" class="hover:underline">Arbitrages</a> <x-icon name="chevron-right" class="size-4" /> <a href="{{ route('tradeoffs.show', $tradeoff) }}" class="hover:underline">{{ $tradeoff->title }}</a> <x-icon name="chevron-right" class="size-4" /> <span aria-current="page">Résultats</span></nav>
    <h1 class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl">Résultats : {{ $tradeoff->title }}</h1>
    <p class="mt-2 text-ink-700">{{ trans_choice(':count participant|:count participants', $results['participants']) }} · {{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($tradeoff->target()) }}</p>

    <section class="mt-8" aria-labelledby="frequence">
        <h2 id="frequence" class="text-2xl font-extrabold tracking-tight">Fréquence de choix de chaque mesure</h2>
        @if ($results['participants'] === 0)
            <div class="mt-6 flex flex-col items-center gap-4 text-center">
                <x-illustration name="empty" />
                <p class="text-ink-500">Personne n'a encore répondu.</p>
            </div>
        @else
            <ol class="mt-4 space-y-3">
                @foreach ($results['items'] as $row)
                    <li class="card">
                        <div class="flex items-baseline justify-between gap-3">
                            <a href="{{ $row['item']->proposal->url() }}" class="text-lg leading-snug font-extrabold tracking-tight text-ink-900 no-underline hover:underline">{{ $row['item']->proposal->title }}</a>
                            <span class="shrink-0 text-sm"><strong class="text-xl font-extrabold text-accent-700">{{ $row['percent'] }} %</strong> <span class="text-ink-700">({{ $row['count'] }})</span></span>
                        </div>
                        <svg class="reveal mt-2 h-3 w-full overflow-hidden rounded-full bg-ink-100" role="img" aria-label="{{ $row['percent'] }} % des participants"><rect x="0" y="0" width="{{ $row['percent'] }}%" height="100%" class="fill-accent-600" /></svg>
                        <p class="mt-2 text-sm text-ink-700">{{ $tradeoff->formatAmount($row['item']->impactValue()) }} · incertitude : {{ $row['item']->uncertainty }}</p>
                        @if ($row['conditions'] !== [])
                            <p class="mt-1 text-sm text-ink-700">Conditions les plus citées :
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
        <section class="mt-10" aria-labelledby="combinaisons">
            <h2 id="combinaisons" class="text-2xl font-extrabold tracking-tight">Combinaisons les plus fréquentes</h2>
            @php $titles = collect($results['items'])->mapWithKeys(fn ($r) => [$r['item']->id => $r['item']->proposal->title]); @endphp
            <ol class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($results['combinations'] as $combination)
                    <li class="card-sand">
                        <p class="font-extrabold"><span class="text-xl text-plum-700">{{ $combination['percent'] }} %</span> des participants · {{ $tradeoff->formatAmount($combination['total']) }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink-700">
                            @foreach ($combination['item_ids'] as $id)
                                <li>{{ $titles[$id] ?? 'Mesure retirée' }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
    <p class="mt-8 text-sm text-ink-700">La ventilation par famille de votants arrivera en V2.</p>
</x-layouts.app>
