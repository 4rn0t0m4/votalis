<x-layouts.app title="Mon parcours">
    @php
        $ring = function (int $value, int $max, string $stroke, string $label): string {
            $circumference = 201;
            $ratio = $max > 0 ? min(1, $value / $max) : 0;
            $offset = (int) round($circumference * (1 - $ratio));
            return sprintf(
                '<div class="relative size-24"><svg viewBox="0 0 80 80" class="size-24" role="img" aria-label="%s"><circle cx="40" cy="40" r="32" fill="none" stroke-width="10" class="stroke-ink-100"/><circle cx="40" cy="40" r="32" fill="none" stroke-width="10" stroke-linecap="round" stroke-dasharray="%d" stroke-dashoffset="%d" transform="rotate(-90 40 40)" class="%s transition-[stroke-dashoffset] duration-700"/></svg><span class="absolute inset-0 flex items-center justify-center text-2xl font-extrabold text-ink-900" aria-hidden="true">%d</span></div>',
                e($label), $circumference, $offset, $stroke, $value,
            );
        };
    @endphp
    <p class="text-sm"><a href="{{ route('account.show') }}" class="link">← Mon compte</a></p>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Mon parcours</h1>
            <p class="mt-2 flex items-center gap-1.5 text-sm font-semibold text-ink-700"><x-icon name="lock" class="size-4" /> Visible par vous seul·e. Rien de cette page n'apparaît sur vos contributions.</p>
        </div>
        <x-illustration name="journey" class="w-56" />
    </div>

    @foreach ($fresh as $milestone)
        <x-alert type="celebrate" class="pop mt-6">
            <span class="eyebrow block text-plum-700">Jalon atteint</span>
            <strong class="text-base">{{ $milestone->label() }}</strong> · {{ $milestone->rule() }}
        </x-alert>
    @endforeach

    <section class="card mt-8" aria-labelledby="chiffres">
        <h2 id="chiffres" class="eyebrow text-ink-700">Depuis le {{ $stats['since']?->translatedFormat('j F Y') }}</h2>
        <div class="mt-5 grid grid-cols-3 gap-4">
            <div class="flex flex-col items-center gap-2 text-center">
                {!! $ring($stats['themes'], $stats['themes_total'], 'stroke-accent-600', "{$stats['themes']} thèmes explorés sur {$stats['themes_total']}") !!}
                <p class="text-sm font-bold text-ink-700">thèmes explorés sur {{ $stats['themes_total'] }}</p>
            </div>
            <div class="flex flex-col items-center gap-2 text-center">
                {!! $ring($stats['votes'], max(1, $stats['votes']), 'stroke-plum-600', "{$stats['votes']} fiches votées") !!}
                <p class="text-sm font-bold text-ink-700">fiches votées</p>
            </div>
            <div class="flex flex-col items-center gap-2 text-center">
                {!! $ring($stats['revised'], max(1, $stats['votes']), 'stroke-ink-900', "{$stats['revised']} avis révisés après lecture des arguments") !!}
                <p class="text-sm font-bold text-ink-700">avis révisés après lecture</p>
            </div>
        </div>
        <p class="mt-5 text-sm text-ink-700">
            {{ trans_choice(':count arbitrage validé|:count arbitrages validés', $stats['answers']) }}
            · {{ trans_choice(':count argument jugé utile|:count arguments jugés utiles', $stats['useful']) }}
            · {{ trans_choice(':count argument publié|:count arguments publiés', $stats['arguments']) }}
            · {{ trans_choice(':count proposition publiée|:count propositions publiées', $stats['proposals']) }}
        </p>
    </section>

    <section class="mt-8" aria-labelledby="jalons">
        <div class="flex items-baseline justify-between gap-3">
            <h2 id="jalons" class="text-2xl font-extrabold tracking-tight">Jalons</h2>
            <p class="font-extrabold text-plum-700">{{ count($reached) }} sur {{ count($milestones) }}</p>
        </div>
        <p class="mt-1 text-sm text-ink-700">Chaque jalon marque une pratique du débat, jamais une quantité. La règle est écrite sous chacun.</p>
        <ul class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($milestones as $milestone)
                @php $date = $reached[$milestone->value] ?? null; @endphp
                <li class="flex items-center gap-4 rounded-2xl p-4 {{ $date ? 'bg-plum-100' : 'border-2 border-dashed border-ink-200 bg-white' }}">
                    <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full {{ $date ? 'bg-plum-600 text-white' : 'bg-ink-100 text-ink-700' }}" aria-hidden="true"><x-icon :name="$milestone->icon()" class="size-6" /></span>
                    <div class="min-w-0">
                        <p class="font-extrabold {{ $date ? '' : 'text-ink-700' }}">{{ $milestone->label() }}@if ($date) <span class="sr-only">, atteint</span>@endif</p>
                        <p class="text-sm text-ink-700">{{ $milestone->rule() }}@if ($date) · atteint le {{ $date->translatedFormat('j F Y') }}@endif</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
