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
    @if (! empty($consensus))
        @php $consensusScore = $consensus->score === null ? null : (int) round(100 * (float) $consensus->score); @endphp
        <div class="rounded-2xl bg-mist-100 p-4" aria-labelledby="consensus-{{ $consensus->proposal_id }}">
            <p id="consensus-{{ $consensus->proposal_id }}" class="eyebrow text-ink-700">Accord par groupe de votants</p>
            <p class="mt-1 text-sm text-ink-700">Les votants sont regroupés selon la ressemblance de leurs votes sur l'ensemble des fiches. Les groupes ne sont ni nommés ni décrits. <a href="{{ route('ranking-explained') }}#consensus" class="link">Comment c'est calculé</a></p>
            <ul class="mt-3 space-y-2.5">
                @foreach ($consensus->groups as $group)
                    @php
                        $agree = (int) round(100 * $group['agree_rate']);
                        $needed = (int) round(100 * $group['necessary_rate']);
                    @endphp
                    <li>
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3 text-sm">
                            <span class="font-bold text-ink-900">Groupe {{ $group['label'] }}</span>
                            @if ($group['represented'])
                                <span class="text-ink-700"><strong class="text-accent-700">{{ $agree }} %</strong> la jugent souhaitable · {{ $needed }} % nécessaire · {{ trans_choice(':count votant|:count votants', $group['voters']) }}</span>
                            @else
                                <span class="text-ink-700">trop peu de votants ({{ $group['voters'] }}) pour compter</span>
                            @endif
                        </div>
                        @if ($group['represented'])
                            <svg class="reveal mt-1 h-2.5 w-full overflow-hidden rounded-full bg-white" role="img" aria-label="Groupe {{ $group['label'] }} : {{ $agree }} % la jugent souhaitable">
                                <rect x="0" y="0" width="{{ $agree }}%" height="100%" rx="5" class="fill-accent-600" />
                            </svg>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-sm font-semibold text-ink-900">
                @if ($consensusScore !== null)
                    Accord minimal entre les groupes : {{ $consensusScore }} %.
                @else
                    Pas assez de votants dans au moins deux groupes pour classer cette fiche par consensus.
                @endif
            </p>
        </div>
    @endif
</div>
