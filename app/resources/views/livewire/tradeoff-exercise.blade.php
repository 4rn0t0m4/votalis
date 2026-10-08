<div class="space-y-6">
    @if (session('tradeoff-status'))
        <x-alert type="celebrate" class="pop">{{ session('tradeoff-status') }}</x-alert>
    @endif
    @include('partials.celebrations', ['milestones' => array_map(fn (string $k) => \App\Enums\Milestone::from($k), $celebrations)])
    @error('tradeoff') <x-alert type="error">{{ $message }}</x-alert> @enderror
    @error('items') <x-alert type="error">{{ $message }}</x-alert> @enderror
    @error('conditions') <x-alert type="error">{{ $message }}</x-alert> @enderror

    {{-- Jauge --}}
    <section class="card {{ $satisfied ? 'border-[3px] border-accent-600' : 'border-[3px] border-transparent' }} transition-colors duration-300" aria-labelledby="jauge">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="jauge" class="eyebrow text-ink-700">Votre combinaison</h2>
                <p class="mt-1 text-4xl font-extrabold tracking-tight {{ $satisfied ? 'text-accent-700' : 'text-ink-900' }} transition-colors duration-300" aria-live="polite">{{ $tradeoff->formatAmount($total) }}</p>
            </div>
            <p class="text-right text-sm text-ink-700">objectif<br><strong class="text-lg text-ink-900">{{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($target) }}</strong></p>
        </div>
        <svg class="mt-4 h-4 w-full overflow-hidden rounded-full bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="Progression vers la contrainte">
            <rect x="0" y="0" width="{{ $progress }}%" height="100%" rx="8" class="{{ $satisfied ? 'fill-accent-600' : 'fill-plum-500' }} transition-all duration-500" />
        </svg>
        <p class="mt-3 flex items-center gap-2 text-sm font-semibold {{ $satisfied ? 'text-accent-700' : 'text-ink-700' }}" aria-live="polite">
            <x-icon :name="$satisfied ? 'check' : 'sparkle'" class="size-4" />
            {{ $satisfied ? 'Contrainte atteinte : vous pouvez valider.' : 'Contrainte non atteinte : ajoutez ou retirez des mesures.' }}
        </p>
        <p class="mt-1 text-sm text-ink-700">{{ trans_choice(':count mesure retenue|:count mesures retenues', count($selected)) }} sur {{ $tradeoff->items->count() }}.</p>
    </section>

    {{-- Mesures --}}
    <ul class="space-y-3" aria-label="Mesures candidates">
        @foreach ($tradeoff->items as $item)
            @php $checked = in_array($item->id, $selected, true); @endphp
            <li class="card-flat transition-colors duration-150 {{ $checked ? 'border-accent-600 bg-accent-50' : '' }}" wire:key="item-{{ $item->id }}">
                <div class="flex items-start gap-4">
                    @if ($canAnswer && $editing)
                        <input id="item-{{ $item->id }}" type="checkbox" wire:click="toggle({{ $item->id }})" @checked($checked) class="mt-1 size-6 shrink-0 rounded-md border-ink-300 accent-accent-600">
                    @else
                        <span class="mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-md border-2 {{ $checked ? 'border-accent-600 bg-accent-600 text-white' : 'border-ink-300' }}" aria-hidden="true">@if ($checked)<x-icon name="check" class="size-4" stroke="3" />@endif</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <label for="item-{{ $item->id }}" class="text-lg leading-snug font-extrabold tracking-tight">{{ $item->proposal->title }}</label>
                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-700">
                            <span class="text-base font-extrabold text-plum-700">{{ $tradeoff->formatAmount($item->impactValue()) }}</span>
                            <span>incertitude : {{ $item->uncertainty }}</span>
                            <a href="{{ $item->source_url }}" rel="noopener nofollow" class="link">source du chiffrage</a>
                            <a href="{{ $item->proposal->url() }}" class="link">fiche</a>
                        </p>
                        <details class="group mt-2 text-sm">
                            <summary class="inline-flex min-h-9 cursor-pointer list-none items-center gap-1 font-semibold text-ink-700 [&::-webkit-details-marker]:hidden"><x-icon name="chevron-down" class="size-4 transition-transform group-open:rotate-180" /> Arguments pour et contre</summary>
                            @php
                                $args = $item->proposal->arguments()->published()->with('author')->latest()->limit(6)->get();
                            @endphp
                            @if ($args->isEmpty())
                                <p class="mt-2 text-ink-700">Aucun argument pour l'instant. <a href="{{ $item->proposal->url() }}" class="link">En ajouter sur la fiche</a>.</p>
                            @else
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    @foreach (['for' => 'Pour', 'against' => 'Contre'] as $sideKey => $sideLabel)
                                        <div class="rounded-2xl bg-ink-50 p-3">
                                            <p class="font-bold">{{ $sideLabel }}</p>
                                            <ul class="mt-1 list-disc space-y-1 pl-4 text-ink-700">
                                                @forelse ($args->where('side.value', $sideKey) as $arg)
                                                    <li>{{ $arg->body }}</li>
                                                @empty
                                                    <li class="list-none text-ink-500">Aucun</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </details>
                        @if ($checked && $canAnswer && $editing)
                            <label for="condition-{{ $item->id }}" class="mt-3 block text-sm font-bold">Acceptée à condition que… (facultatif)</label>
                            <input id="condition-{{ $item->id }}" type="text" wire:model="conditions.{{ $item->id }}" maxlength="200" class="field mt-1 text-sm">
                        @elseif ($checked && ! empty($conditions[$item->id]))
                            <p class="mt-2 text-sm text-ink-700">À condition que {{ $conditions[$item->id] }}</p>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    {{-- Actions --}}
    @guest
        <p class="text-ink-700"><a href="{{ route('login') }}" class="link">Connectez-vous</a> pour composer votre combinaison.</p>
    @endguest
    @if ($canAnswer)
        @if ($editing)
            <div class="sticky bottom-0 z-10 -mx-4 border-t border-ink-200 bg-ink-50/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0">
                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="button" wire:click="submit" :disabled="! $satisfied" size="lg" class="w-full sm:w-auto">Valider ma combinaison <x-icon name="check" class="size-5" /></x-button>
                    @if ($answer) <button type="button" wire:click="$set('editing', false)" class="btn btn-ghost">Annuler</button> @endif
                </div>
            </div>
        @else
            <button type="button" wire:click="edit" class="btn btn-secondary"><x-icon name="refresh" class="size-4" /> Refaire l'arbitrage</button>
        @endif
    @elseif (auth()->check() && ! $tradeoff->isOpen())
        <p class="text-sm text-ink-700">Cet arbitrage est clos. <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="link">Voir les résultats</a>.</p>
    @endif

    {{-- Historique du participant --}}
    @if ($history->count() > 1)
        <section class="card-flat" aria-labelledby="historique-arbitrage">
            <h2 id="historique-arbitrage" class="font-bold">Vos combinaisons précédentes (visibles par vous seul)</h2>
            <ol class="mt-2 space-y-1 text-sm text-ink-700">
                @foreach ($history->skip(1) as $revision)
                    <li>{{ $revision->created_at->translatedFormat('j F Y à H\hi') }} · {{ count($revision->item_ids) }} mesures · {{ $tradeoff->formatAmount((float) $revision->total) }}</li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Suggestion --}}
    @if ($canAnswer && $candidates->isNotEmpty())
        <details class="card-flat group" aria-labelledby="suggerer">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 [&::-webkit-details-marker]:hidden">
                <span>
                    <span id="suggerer" class="font-bold">Proposer une mesure candidate</span>
                    <span class="block text-sm text-ink-700">Le comité éditorial l'ajoutera après vérification de son chiffrage.</span>
                </span>
                <x-icon name="chevron-down" class="size-5 transition-transform group-open:rotate-180" />
            </summary>
            <form wire:submit="suggest" class="mt-4 space-y-3" novalidate>
                <label for="suggestion-proposal" class="block text-sm font-bold">Proposition</label>
                <select id="suggestion-proposal" wire:model="suggestionProposalId" class="field">
                    <option value="">Choisir…</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->title }}</option>
                    @endforeach
                </select>
                @error('suggestionProposalId') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                @error('proposal_id') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                <label for="suggestion-note" class="block text-sm font-bold">Chiffrage ou source suggérés (facultatif)</label>
                <input id="suggestion-note" type="text" wire:model="suggestionNote" maxlength="500" class="field">
                @error('note') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                <x-button variant="secondary">Proposer</x-button>
            </form>
        </details>
    @endif
</div>
