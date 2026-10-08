<div class="mx-auto max-w-xl">
    @if ($proposal === null)
        <div class="rounded-lg border border-ink-200 bg-white p-6 text-center">
            <p class="font-medium">Vous avez donné votre avis sur toutes les propositions disponibles.</p>
            <p class="mt-2 text-sm text-ink-700">Revenez plus tard, ou <a href="{{ route('themes.index') }}" class="underline">parcourez les thèmes</a>.</p>
        </div>
    @else
        <article class="rounded-lg border border-ink-200 bg-white p-5" wire:key="quick-{{ $proposal->id }}">
            <p class="text-xs text-ink-500">{{ $proposal->theme->fullName() }} · {{ $proposal->origin->label() }}</p>
            <h2 class="mt-1 text-xl font-semibold"><a href="{{ $proposal->url() }}" class="hover:underline">{{ $proposal->title }}</a></h2>
            <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-ink-500">Problème visé</h3>
            <p class="mt-1 text-sm">{{ $proposal->problem }}</p>
            <h3 class="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-500">Mesure proposée</h3>
            <p class="mt-1 whitespace-pre-line text-sm">{{ $proposal->measure }}</p>
            <p class="mt-3 text-sm"><span class="font-medium">Coût ou impact :</span> {{ $proposal->cost_unknown ? 'inconnu' : $proposal->cost_estimate }}</p>

            @if ($argumentsShown)
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::For" :key="'qv-for-'.$proposal->id" />
                    <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::Against" :key="'qv-against-'.$proposal->id" />
                </div>
            @else
                <button type="button" wire:click="showArguments" class="mt-4 text-sm underline">Voir les arguments pour et contre</button>
            @endif
        </article>

        <div class="mt-4">
            <livewire:vote-box :proposal="$proposal" :arguments-visible="$argumentsShown" :key="'qv-vote-'.$proposal->id.'-'.($argumentsShown ? 'a' : 'n')" />
        </div>

        <div class="mt-4 flex items-center justify-between">
            @if ($justVoted)
                <x-button type="button" wire:click="next" class="w-full">Proposition suivante</x-button>
            @else
                <button type="button" wire:click="skip" class="text-sm underline">Passer cette proposition</button>
            @endif
        </div>
    @endif
</div>
