<div class="mx-auto max-w-2xl">
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-ink-700" aria-live="polite">{{ trans_choice(':count avis donné aujourd’hui|:count avis donnés aujourd’hui', $votesToday) }}</p>
        <a href="{{ route('themes.index') }}" class="text-sm font-semibold text-ink-700 hover:underline">Parcourir les thèmes</a>
    </div>

    @if ($proposal === null)
        <x-card class="pop flex flex-col items-center gap-4 text-center">
            <x-illustration name="empty" />
            <p class="text-xl font-extrabold tracking-tight">Vous avez donné votre avis sur toutes les propositions disponibles.</p>
            <p class="text-ink-700">Revenez plus tard, ou <a href="{{ route('themes.index') }}" class="link">parcourez les thèmes</a>.</p>
        </x-card>
    @else
        <div class="relative pt-3">
            {{-- Pile de cartes : deux cartes décoratives derrière la fiche courante. --}}
            <div class="absolute inset-x-6 top-0 h-10 rounded-3xl bg-sand-200" aria-hidden="true"></div>
            <div class="absolute inset-x-3 top-1.5 h-10 rounded-3xl bg-sand-100" aria-hidden="true"></div>

            <article class="card pop relative" wire:key="quick-{{ $proposal->id }}">
                <div class="flex flex-wrap gap-2">
                    <x-pill tone="sand">{{ $proposal->theme->fullName() }}</x-pill>
                    <x-pill tone="lagoon">{{ $proposal->origin->label() }}</x-pill>
                </div>
                <h2 class="mt-3 text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl"><a href="{{ $proposal->url() }}" class="text-ink-900 no-underline hover:underline">{{ $proposal->title }}</a></h2>
                <h3 class="eyebrow mt-5 text-accent-700">Problème visé</h3>
                <p class="mt-1.5 leading-relaxed">{{ $proposal->problem }}</p>
                <h3 class="eyebrow mt-4 text-accent-700">Mesure proposée</h3>
                <p class="mt-1.5 leading-relaxed whitespace-pre-line">{{ $proposal->measure }}</p>
                <p class="mt-4 flex flex-wrap items-baseline gap-2">
                    <span class="text-xl font-extrabold tracking-tight text-plum-700">{{ $proposal->cost_unknown ? 'Coût inconnu' : $proposal->cost_estimate }}</span>
                    <span class="text-sm text-ink-700">coût ou impact estimé</span>
                </p>

                @if ($argumentsShown)
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::For" :key="'qv-for-'.$proposal->id" />
                        <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::Against" :key="'qv-against-'.$proposal->id" />
                    </div>
                @else
                    <button type="button" wire:click="showArguments" class="btn btn-secondary mt-5 w-full"><x-icon name="chevron-down" class="size-4" /> Lire les arguments pour et contre</button>
                @endif
            </article>
        </div>

        <div class="mt-4">
            <livewire:vote-box :proposal="$proposal" :arguments-visible="$argumentsShown" :key="'qv-vote-'.$proposal->id.'-'.($argumentsShown ? 'a' : 'n')" />
        </div>

        {{-- Barre d'actions fixée en bas sur mobile, atteignable au pouce. --}}
        <div class="sticky bottom-0 z-10 -mx-4 mt-4 border-t border-ink-200 bg-ink-50/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0">
            @if ($justVoted)
                <x-button type="button" wire:click="next" size="lg" class="w-full">Proposition suivante <x-icon name="arrow-right" class="size-5" /></x-button>
            @else
                <button type="button" wire:click="skip" class="btn btn-secondary w-full sm:w-auto">Passer cette proposition</button>
            @endif
        </div>
    @endif
</div>
