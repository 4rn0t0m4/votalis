<div class="space-y-6">
    @if (session('tradeoff-status'))
        <x-alert type="success">{{ session('tradeoff-status') }}</x-alert>
    @endif
    @error('tradeoff') <x-alert type="error">{{ $message }}</x-alert> @enderror
    @error('items') <x-alert type="error">{{ $message }}</x-alert> @enderror
    @error('conditions') <x-alert type="error">{{ $message }}</x-alert> @enderror

    {{-- Jauge --}}
    <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="jauge">
        <h2 id="jauge" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Votre combinaison</h2>
        <div class="mt-2 flex items-baseline justify-between">
            <p class="text-2xl font-semibold" aria-live="polite">{{ $tradeoff->formatAmount($total) }}</p>
            <p class="text-sm text-ink-500">{{ mb_strtolower($tradeoff->direction->label()) }} {{ $tradeoff->formatAmount($target) }}</p>
        </div>
        <div class="mt-2 h-3 w-full overflow-hidden rounded bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="Progression vers la contrainte">
            <div class="h-3 {{ $satisfied ? 'bg-accent-600' : 'bg-ink-500' }} w-[{{ $progress }}%]"></div>
        </div>
        <p class="mt-2 text-sm {{ $satisfied ? 'text-accent-700' : 'text-ink-700' }}" aria-live="polite">
            {{ $satisfied ? 'Contrainte atteinte : vous pouvez valider.' : 'Contrainte non atteinte : ajoutez ou retirez des mesures.' }}
        </p>
    </section>

    {{-- Mesures --}}
    <ul class="space-y-3" aria-label="Mesures candidates">
        @foreach ($tradeoff->items as $item)
            @php $checked = in_array($item->id, $selected, true); @endphp
            <li class="rounded-lg border {{ $checked ? 'border-accent-600' : 'border-ink-200' }} bg-white p-4" wire:key="item-{{ $item->id }}">
                <div class="flex items-start gap-3">
                    @if ($canAnswer && $editing)
                        <input id="item-{{ $item->id }}" type="checkbox" wire:click="toggle({{ $item->id }})" @checked($checked) class="mt-1 h-5 w-5 rounded border-ink-300">
                    @else
                        <span class="mt-1 inline-block h-5 w-5 rounded border {{ $checked ? 'border-accent-600 bg-accent-600' : 'border-ink-300' }}" aria-hidden="true"></span>
                    @endif
                    <div class="flex-1">
                        <label for="item-{{ $item->id }}" class="font-medium">{{ $item->proposal->title }}</label>
                        <p class="mt-1 text-sm text-ink-700">
                            <span class="font-semibold">{{ $tradeoff->formatAmount($item->impactValue()) }}</span>
                            <span class="text-ink-500">· incertitude : {{ $item->uncertainty }}</span>
                            · <a href="{{ $item->source_url }}" rel="noopener nofollow" class="underline">source du chiffrage</a>
                            · <a href="{{ $item->proposal->url() }}" class="underline">fiche</a>
                        </p>
                        <details class="mt-2 text-sm">
                            <summary class="cursor-pointer text-ink-700">Arguments pour et contre</summary>
                            @php
                                $args = $item->proposal->arguments()->published()->with('author')->latest()->limit(6)->get();
                            @endphp
                            @if ($args->isEmpty())
                                <p class="mt-2 text-ink-500">Aucun argument pour l'instant. <a href="{{ $item->proposal->url() }}" class="underline">En ajouter sur la fiche</a>.</p>
                            @else
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach (['for' => 'Pour', 'against' => 'Contre'] as $sideKey => $sideLabel)
                                        <div>
                                            <p class="font-medium">{{ $sideLabel }}</p>
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
                            <label for="condition-{{ $item->id }}" class="mt-2 block text-xs font-medium text-ink-700">Acceptée à condition que… (facultatif)</label>
                            <input id="condition-{{ $item->id }}" type="text" wire:model="conditions.{{ $item->id }}" maxlength="200" class="mt-1 block w-full rounded border border-ink-300 px-3 py-1.5 text-sm">
                        @elseif ($checked && ! empty($conditions[$item->id]))
                            <p class="mt-2 text-xs text-ink-700">À condition que {{ $conditions[$item->id] }}</p>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    {{-- Actions --}}
    @guest
        <p class="text-sm text-ink-700"><a href="{{ route('login') }}" class="underline">Connectez-vous</a> pour composer votre combinaison.</p>
    @endguest
    @if ($canAnswer)
        @if ($editing)
            <div class="flex items-center gap-3">
                <x-button type="button" wire:click="submit" :disabled="! $satisfied">Valider ma combinaison</x-button>
                @if ($answer) <button type="button" wire:click="$set('editing', false)" class="text-sm underline">Annuler</button> @endif
            </div>
        @else
            <button type="button" wire:click="edit" class="text-sm underline">Refaire l'arbitrage</button>
        @endif
    @elseif (auth()->check() && ! $tradeoff->isOpen())
        <p class="text-sm text-ink-500">Cet arbitrage est clos. <a href="{{ route('tradeoffs.results', $tradeoff) }}" class="underline">Voir les résultats</a>.</p>
    @endif

    {{-- Historique du participant --}}
    @if ($history->count() > 1)
        <section class="rounded-lg border border-ink-200 bg-white p-4" aria-labelledby="historique-arbitrage">
            <h2 id="historique-arbitrage" class="text-sm font-semibold">Vos combinaisons précédentes (visibles par vous seul)</h2>
            <ol class="mt-2 space-y-1 text-sm text-ink-700">
                @foreach ($history->skip(1) as $revision)
                    <li>{{ $revision->created_at->translatedFormat('j F Y à H\hi') }} · {{ count($revision->item_ids) }} mesures · {{ $tradeoff->formatAmount((float) $revision->total) }}</li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Suggestion --}}
    @if ($canAnswer && $candidates->isNotEmpty())
        <section class="rounded-lg border border-ink-200 bg-white p-4" aria-labelledby="suggerer">
            <h2 id="suggerer" class="text-sm font-semibold">Proposer une mesure candidate</h2>
            <p class="mt-1 text-sm text-ink-700">Le comité éditorial l'ajoutera après vérification de son chiffrage.</p>
            <form wire:submit="suggest" class="mt-3 space-y-2" novalidate>
                <label for="suggestion-proposal" class="block text-sm font-medium">Proposition</label>
                <select id="suggestion-proposal" wire:model="suggestionProposalId" class="block w-full rounded border border-ink-300 bg-white px-3 py-2 text-sm">
                    <option value="">Choisir…</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->title }}</option>
                    @endforeach
                </select>
                @error('suggestionProposalId') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                @error('proposal_id') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                <label for="suggestion-note" class="block text-sm font-medium">Chiffrage ou source suggérés (facultatif)</label>
                <input id="suggestion-note" type="text" wire:model="suggestionNote" maxlength="500" class="block w-full rounded border border-ink-300 px-3 py-2 text-sm">
                @error('note') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                <x-button variant="secondary">Proposer</x-button>
            </form>
        </section>
    @endif
</div>
