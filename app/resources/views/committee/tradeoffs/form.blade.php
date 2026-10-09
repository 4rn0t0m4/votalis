<x-layouts.app :title="$tradeoff->exists ? 'Modifier l’arbitrage' : 'Nouvel arbitrage'">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $tradeoff->exists ? $tradeoff->title : 'Nouvel arbitrage' }}</h1>
    @if ($tradeoff->exists)
        <p class="mt-1 text-sm text-ink-700">Statut : {{ $tradeoff->status->label() }} · <a href="{{ route('tradeoffs.show', $tradeoff) }}" class="link">voir la page publique</a></p>
    @endif
    @if ($errors->has('items')) <x-alert type="error" class="mt-4">{{ $errors->first('items') }}</x-alert> @endif
    @if ($errors->has('status')) <x-alert type="error" class="mt-4">{{ $errors->first('status') }}</x-alert> @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-2">
        <form method="POST" action="{{ $tradeoff->exists ? route('committee.tradeoffs.update', $tradeoff) : route('committee.tradeoffs.store') }}" class="max-w-lg">
            @csrf
            @if ($tradeoff->exists) @method('PUT')@endif
            <h2 class="mb-3 text-lg font-semibold">Exercice</h2>
            <x-form.field name="title" label="Titre" required :value="$tradeoff->title" maxlength="120" help="Ex. « Trouver 40 milliards d’économies ou de recettes »." />
            <div class="mb-4">
                <label for="objective" class="mb-1.5 block font-bold">Objectif chiffré et sourcé <span aria-hidden="true">*</span></label>
                <textarea id="objective" name="objective" rows="3" required maxlength="1000" class="field">{{ old('objective', $tradeoff->objective) }}</textarea>
                @error('objective') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-3">
                <x-form.field name="constraint_value" label="Contrainte" type="number" required :value="$tradeoff->constraint_value" />
                <x-form.field name="unit" label="Unité" required :value="$tradeoff->unit ?? 'Md€'" maxlength="30" />
                <div class="mb-4">
                    <label for="direction" class="mb-1.5 block font-bold">Sens</label>
                    <select id="direction" name="direction" class="field">
                        @foreach (\App\Enums\TradeoffDirection::cases() as $direction)
                            <option value="{{ $direction->value }}" @selected(old('direction', $tradeoff->direction?->value ?? 'at_least') === $direction->value)>{{ $direction->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-form.field name="source_url" label="Source de l’objectif (URL)" type="url" :value="$tradeoff->source_url" />
            <x-form.field name="source_label" label="Libellé de la source" :value="$tradeoff->source_label" maxlength="200" />
            <div class="mb-4">
                <label for="theme_id" class="mb-1.5 block font-bold">Thème (facultatif)</label>
                <select id="theme_id" name="theme_id" class="field">
                    <option value="">Aucun</option>
                    @foreach ($themes as $theme)
                        <option value="{{ $theme->id }}" @selected((int) old('theme_id', $tradeoff->theme_id) === $theme->id)>{{ $theme->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-button>Enregistrer</x-button>
        </form>

        @if ($tradeoff->exists)
            <div class="space-y-8">
                <section aria-labelledby="mesures">
                    <h2 id="mesures" class="text-xl font-extrabold tracking-tight">Mesures candidates ({{ $tradeoff->items->count() }})</h2>
                    <p class="mt-1 text-sm text-ink-700">Chiffrage, incertitude et source obligatoires : une mesure sans chiffrage fiable n'entre pas dans un arbitrage.</p>
                    <ul class="mt-3 divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white text-sm">
                        @forelse ($tradeoff->items as $item)
                            <li class="flex items-center justify-between gap-2 p-3">
                                <span>{{ $item->proposal->title }} <span class="text-ink-500">· {{ $tradeoff->formatAmount($item->impactValue()) }} · {{ $item->uncertainty }}</span></span>
                                <form method="POST" action="{{ route('committee.tradeoffs.items.destroy', [$tradeoff, $item]) }}">
                                    @csrf @method('DELETE')
                                    <x-button variant="danger" class="px-2 py-1">Retirer</x-button>
                                </form>
                            </li>
                        @empty
                            <li class="p-3 text-ink-500">Aucune mesure.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('committee.tradeoffs.items.store', $tradeoff) }}" class="mt-4 card">
                        @csrf
                        <h3 class="mb-2 font-medium">Ajouter une mesure</h3>
                        <div class="mb-3">
                            <label for="proposal_id" class="mb-1.5 block font-bold">Proposition</label>
                            <select id="proposal_id" name="proposal_id" required class="field text-sm">
                                <option value="">Choisir…</option>
                                @foreach ($candidates ?? [] as $candidate)
                                    <option value="{{ $candidate->id }}" @selected((int) old('proposal_id') === $candidate->id)>{{ $candidate->title }}</option>
                                @endforeach
                            </select>
                            @error('proposal_id') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <x-form.field name="impact" :label="'Impact ('.$tradeoff->unit.')'" type="number" required help="Positif si la mesure contribue à l’objectif." />
                            <x-form.field name="uncertainty" label="Incertitude" required maxlength="120" help="Ex. « ± 20 % », « ordre de grandeur »." />
                        </div>
                        <x-form.field name="source_url" label="Source du chiffrage (URL)" type="url" required />
                        <x-button variant="secondary">Ajouter</x-button>
                    </form>
                </section>

                <section aria-labelledby="statut">
                    <h2 id="statut" class="text-xl font-extrabold tracking-tight">Statut</h2>
                    <form method="POST" action="{{ route('committee.tradeoffs.status', $tradeoff) }}" class="mt-2 flex items-center gap-2">
                        @csrf
                        <label for="status" class="sr-only">Statut</label>
                        <select id="status" name="status" class="rounded border border-ink-300 bg-white px-3 py-2 text-sm">
                            @foreach (\App\Enums\TradeoffStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($tradeoff->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-button variant="secondary">Changer</x-button>
                    </form>
                </section>

                @php $pending = $tradeoff->suggestions->where('status', \App\Enums\SuggestionStatus::Pending); @endphp
                <section aria-labelledby="suggestions">
                    <h2 id="suggestions" class="text-xl font-extrabold tracking-tight">Mesures suggérées par les participants ({{ $pending->count() }})</h2>
                    <ul class="mt-3 divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white text-sm">
                        @forelse ($pending as $suggestion)
                            <li class="p-3">
                                <p class="font-medium">{{ $suggestion->proposal->title }}</p>
                                <p class="text-xs text-ink-700">{{ $suggestion->participant?->pseudonym ?? 'Participant supprimé' }}@if ($suggestion->note) · {{ $suggestion->note }}@endif</p>
                                <p class="mt-1 text-xs">Pour l'ajouter, utilisez le formulaire « Ajouter une mesure » avec son chiffrage vérifié.</p>
                                <form method="POST" action="{{ route('committee.tradeoffs.suggestions.reject', [$tradeoff, $suggestion]) }}" class="mt-1">
                                    @csrf
                                    <x-button variant="danger" class="px-2 py-1">Écarter</x-button>
                                </form>
                            </li>
                        @empty
                            <li class="p-3 text-ink-500">Aucune suggestion en attente.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        @endif
    </div>
</x-layouts.app>
