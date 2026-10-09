{{-- Parcours de démarrage : trois pas, visibles du seul participant, tant qu'ils ne sont pas tous faits. --}}
@php
    $steps = app(\App\Services\Journey::class)->onboarding(auth()->user());
    $done = count(array_filter($steps, fn (array $s) => $s['done']));
    $links = [
        \App\Enums\Milestone::FirstVoice->value => ['Vote rapide, 2 minutes', route('quick-vote')],
        \App\Enums\Milestone::FullReading->value => ['Lire les arguments puis voter', route('quick-vote')],
        \App\Enums\Milestone::Arbiter->value => ['Choisir un arbitrage ouvert', route('tradeoffs.index')],
    ];
@endphp
@if ($done < count($steps))
    <section class="card-flat mt-14 border-plum-100" aria-labelledby="demarrage">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="demarrage" class="text-2xl font-extrabold tracking-tight">Bienvenue, {{ auth()->user()->pseudonym }}. Trois pas pour commencer.</h2>
                <p class="mt-1 text-sm text-ink-700">Visible par vous seul·e. Disparaît une fois les trois pas faits.</p>
            </div>
            <p class="font-extrabold text-plum-700">{{ $done }} sur {{ count($steps) }}</p>
        </div>
        <ol class="mt-5 grid gap-3 md:grid-cols-3">
            @foreach ($steps as $i => $step)
                <li class="flex items-start gap-3 rounded-2xl p-4 {{ $step['done'] ? 'bg-plum-100' : 'border-2 border-dashed border-ink-200 bg-ink-50' }}">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full font-extrabold {{ $step['done'] ? 'bg-plum-600 text-white' : 'border-2 border-ink-200 bg-white text-ink-700' }}" aria-hidden="true">@if ($step['done'])<x-icon name="check" class="size-5" stroke="3" />@else{{ $i + 1 }}@endif</span>
                    <div>
                        <p class="font-extrabold">{{ $step['milestone']->rule() }}@if ($step['done']) <span class="sr-only">(fait)</span>@endif</p>
                        @if (! $step['done'])
                            <a href="{{ $links[$step['milestone']->value][1] }}" class="link text-sm">{{ $links[$step['milestone']->value][0] }}</a>
                        @else
                            <p class="text-sm text-ink-700">Fait</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif
