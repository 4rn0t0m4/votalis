<form wire:submit="save" class="max-w-2xl space-y-6" novalidate>
    @if ($rewrite ?? false)
        <x-alert type="info">
            La modération vous demande de reformuler cette proposition ({{ $rewriteMotive }}). Vous pouvez en modifier le fond une fois ; elle redeviendra visible dès l'enregistrement.
        </x-alert>
    @elseif ($locked)
        <x-alert type="info">
            Cette proposition a déjà reçu des votes : vous pouvez corriger la forme (fautes, formulation), mais ni le fond, ni le thème, ni le coût, ni les sources. Un changement de fond passe par une variante.
        </x-alert>
    @endif

    @error('cap') <x-alert type="error">{{ $message }}</x-alert> @enderror
    @error('locked') <x-alert type="error">{{ $message }}</x-alert> @enderror

    <div>
        <label for="theme_id" class="mb-1 block text-sm font-medium">Thème <span aria-hidden="true">*</span></label>
        <select id="theme_id" wire:model="theme_id" required aria-required="true" @disabled($locked)
            class="block w-full rounded border border-ink-300 bg-white px-3 py-2 disabled:bg-ink-100" @error('theme_id') aria-invalid="true" aria-describedby="theme_id-erreur" @enderror>
            <option value="">Choisir un thème</option>
            @foreach ($themes as $t)
                <option value="{{ $t->id }}">{{ $t->fullName() }}</option>
            @endforeach
        </select>
        @error('theme_id') <p id="theme_id-erreur" class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
    </div>

    <x-form.counted-field name="title" label="Titre" :max="$limits['title']" :value="$title"
        help="Formulez une mesure, avec un verbe d'action. Ex. « Plafonner les dépassements d'honoraires »." />

    <x-form.counted-field name="problem" label="Problème visé" :max="$limits['problem']" :value="$problem" rows="4"
        help="Quel problème concret cette mesure cherche-t-elle à résoudre ?" />

    <x-form.counted-field name="measure" label="Mesure proposée" :max="$limits['measure']" :value="$measure" rows="10"
        help="Ce qui changerait concrètement : qui, quoi, comment." />

    @if ($similar !== [])
        <aside class="rounded-lg border border-accent-500/40 bg-accent-100/40 p-4" aria-live="polite" aria-labelledby="similaires">
            <h2 id="similaires" class="text-sm font-semibold">Des propositions proches existent déjà</h2>
            <p class="mt-1 text-sm text-ink-700">Avant de déposer, vérifiez qu'il ne s'agit pas de la même mesure. Vous pouvez la soutenir en votant, ou déposer quand même si la vôtre est différente.</p>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($similar as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded bg-white px-3 py-2" wire:key="similar-{{ $item['id'] }}">
                        <span><a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="font-medium underline">{{ $item['title'] }}</a> <span class="text-ink-500">· {{ $item['theme'] }} · {{ $item['similarity'] }} % de similarité</span></span>
                        <span class="flex gap-2">
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="rounded border border-ink-300 px-2 py-0.5 text-xs hover:bg-ink-100">Soutenir</a>
                            <span class="rounded border border-ink-200 px-2 py-0.5 text-xs text-ink-500" title="Les variantes arrivent en V2">Proposer une variante (bientôt)</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-ink-500">Vous pouvez continuer et déposer quand même : le formulaire reste actif.</p>
        </aside>
    @elseif ($similarChecked && ! $locked)
        <p class="text-sm text-ink-500" aria-live="polite">Aucune proposition proche trouvée.</p>
    @endif

    <fieldset>
        <legend class="mb-1 block text-sm font-medium">Coût ou impact estimé <span aria-hidden="true">*</span></legend>
        <x-form.counted-field name="cost_estimate" label="Estimation" :max="$limits['cost_estimate']" :value="$cost_estimate" :disabled="$cost_unknown || $locked" labelClass="sr-only"
            help="Ordre de grandeur et source si possible. Ex. « 3 milliards d'euros par an (Cour des comptes, 2024) »." />
        <div class="flex items-center gap-2">
            <input id="cost_unknown" type="checkbox" wire:model.live="cost_unknown" @disabled($locked) class="h-4 w-4 rounded border-ink-300">
            <label for="cost_unknown" class="text-sm">Coût ou impact inconnu</label>
        </div>
    </fieldset>

    <fieldset>
        <legend class="mb-1 block text-sm font-medium">Sources <span aria-hidden="true">*</span></legend>
        <p class="mb-2 text-sm text-ink-500">Au moins une adresse web, ou cochez « proposition personnelle ».</p>
        <div class="space-y-2">
            @foreach ($sources as $i => $source)
                <div class="flex gap-2" wire:key="source-{{ $i }}">
                    <label for="source-{{ $i }}" class="sr-only">Source {{ $i + 1 }}</label>
                    <input id="source-{{ $i }}" type="url" wire:model="sources.{{ $i }}" placeholder="https://" @disabled($locked) inputmode="url"
                        class="block w-full rounded border border-ink-300 bg-white px-3 py-2 disabled:bg-ink-100" @error("sources.$i") aria-invalid="true" @enderror>
                    @if (count($sources) > 1 && ! $locked)
                        <button type="button" wire:click="removeSource({{ $i }})" class="rounded border border-ink-300 px-3 text-sm hover:bg-ink-100" aria-label="Retirer la source {{ $i + 1 }}">Retirer</button>
                    @endif
                </div>
                @error("sources.$i") <p class="text-sm text-red-800">{{ $message }}</p> @enderror
            @endforeach
        </div>
        @if (! $locked && count($sources) < 10)
            <button type="button" wire:click="addSource" class="mt-2 text-sm underline">Ajouter une source</button>
        @endif
        <div class="mt-3 flex items-center gap-2">
            <input id="personal_source" type="checkbox" wire:model="personal_source" @disabled($locked) class="h-4 w-4 rounded border-ink-300">
            <label for="personal_source" class="text-sm">Proposition personnelle, sans source externe</label>
        </div>
        @error('sources') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
    </fieldset>

    <div class="flex items-center gap-3">
        <x-button>{{ $proposal ? 'Enregistrer la correction' : 'Publier la proposition' }}</x-button>
        <span wire:loading class="text-sm text-ink-500">Enregistrement…</span>
    </div>
</form>
