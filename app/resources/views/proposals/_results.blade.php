<div class="mt-4 space-y-3" aria-label="Résultats détaillés">
    <p class="text-sm text-ink-500">{{ trans_choice(':count vote|:count votes', $results['total']) }}@if ($results['conditional'] > 0), dont {{ trans_choice(':count oui conditionnel|:count oui conditionnels', $results['conditional']) }}@endif</p>
    @foreach (['desirable' => 'Souhaitable', 'necessary' => 'Nécessaire'] as $key => $label)
        <div>
            <p class="text-sm font-medium">{{ $label }}</p>
            <ul class="mt-1 grid grid-cols-3 gap-2 text-sm">
                @foreach (['yes' => 'Oui', 'no' => 'Non', 'unsure' => 'Je ne sais pas'] as $k => $l)
                    <li class="rounded bg-ink-100 px-2 py-1"><span class="font-semibold">{{ \App\Services\VoteService::percent($results[$key][$k], $results['total']) }} %</span> {{ $l }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
    @if ($results['conditions'] !== [])
        <div>
            <p class="text-sm font-medium">Conditions exprimées</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5 text-sm text-ink-700">
                @foreach (array_slice($results['conditions'], 0, 10) as $condition)
                    <li>{{ $condition }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
