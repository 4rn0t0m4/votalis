@php
    $labels = ['yes' => 'oui', 'no' => 'non', 'unsure' => 'je ne sais pas'];
    $fills = ['yes' => 'fill-accent-600', 'no' => 'fill-ink-900', 'unsure' => 'fill-ink-300'];
    $texts = ['yes' => 'text-accent-700', 'no' => 'text-ink-900', 'unsure' => 'text-ink-700'];
@endphp
<div class="mt-5 space-y-4" aria-label="Résultats détaillés">
    <p class="text-sm font-semibold text-ink-700">{{ trans_choice(':count vote|:count votes', $results['total']) }}@if ($results['conditional'] > 0), dont {{ trans_choice(':count oui conditionnel|:count oui conditionnels', $results['conditional']) }}@endif</p>
    @foreach (['desirable' => 'Souhaitable', 'necessary' => 'Nécessaire'] as $key => $label)
        @php
            $percents = [];
            foreach (['yes', 'no', 'unsure'] as $k) {
                $percents[$k] = \App\Services\VoteService::percent($results[$key][$k], $results['total']);
            }
            $offset = 0;
        @endphp
        <div>
            <p class="eyebrow text-ink-700">{{ $label }}</p>
            <svg class="reveal mt-1.5 h-4 w-full overflow-hidden rounded-full bg-ink-100" role="img" aria-label="{{ $label }} : {{ $percents['yes'] }} % oui, {{ $percents['no'] }} % non, {{ $percents['unsure'] }} % je ne sais pas">
                @foreach ($percents as $k => $percent)
                    <rect x="{{ $offset }}%" y="0" width="{{ $percent }}%" height="100%" class="{{ $fills[$k] }}" />
                    @php $offset += $percent; @endphp
                @endforeach
            </svg>
            <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-700">
                @foreach ($percents as $k => $percent)
                    <li><strong class="{{ $texts[$k] }}">{{ $percent }} %</strong> {{ $labels[$k] }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
    @if ($results['necessary']['yes'] > $results['desirable']['yes'] && $results['total'] >= 3)
        <p class="text-sm text-ink-700">Jugée plus nécessaire que souhaitée : c'est le genre de mesure que les arbitrages départagent.</p>
    @endif
    @if ($results['conditions'] !== [])
        <div>
            <p class="eyebrow text-ink-700">Conditions exprimées</p>
            <ul class="mt-1.5 flex flex-wrap gap-2">
                @foreach (array_slice($results['conditions'], 0, 10) as $condition)
                    <li><x-pill tone="sand" class="text-sm font-semibold">{{ $condition }}</x-pill></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
