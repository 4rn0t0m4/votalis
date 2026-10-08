<section class="rounded-lg border border-ink-200 bg-white p-4" aria-labelledby="arguments-{{ $sideKey }}">
    <h2 id="arguments-{{ $sideKey }}" class="text-lg font-semibold">{{ $sideEnum->label() }} <span class="text-sm font-normal text-ink-500">({{ $publishedCount }})</span></h2>

    @if ($arguments->isEmpty())
        <p class="mt-3 text-sm text-ink-500">Aucun argument {{ $sideKey === 'for' ? 'pour' : 'contre' }} pour l'instant.</p>
    @else
        <ul class="mt-3 divide-y divide-ink-200">
            @foreach ($arguments as $argument)
                <li class="py-3" id="argument-{{ $argument->id }}" wire:key="argument-{{ $argument->id }}">
                    @if ($argument->isHidden())
                        <p class="text-sm text-ink-500">Argument masqué par la modération @if ($argument->hidden_motive && ! $argument->hidesEverything()) ({{ $argument->hidden_motive->label() }})@endif · <a href="{{ route('moderation-log.index', ['type' => 'argument']) }}" class="underline">journal</a></p>
                    @else
                    <p class="text-sm">{{ $argument->body }}</p>
                    <p class="mt-1 text-xs text-ink-500">
                        {{ $argument->authorName() }} · {{ $argument->created_at?->translatedFormat('j F Y') }}
                        @if ($argument->source_url)
                            · <a href="{{ $argument->source_url }}" rel="noopener nofollow" class="underline">source</a>
                        @endif
                    </p>
                    <div class="mt-1">
                        @if ($canContribute)
                            <button type="button" wire:click="toggleMark({{ $argument->id }})" aria-pressed="{{ in_array($argument->id, $marked, true) ? 'true' : 'false' }}"
                                class="rounded border px-2 py-0.5 text-xs {{ in_array($argument->id, $marked, true) ? 'border-accent-700 bg-accent-100 text-accent-700' : 'border-ink-300 hover:bg-ink-100' }}">
                                Utile · {{ $argument->marked_by_count }}
                            </button>
                        @else
                            <span class="text-xs text-ink-500">Utile · {{ $argument->marked_by_count }}</span>
                        @endif
                        @if ($canReport && $argument->author_id !== auth()->id())
                            <a href="{{ route('reports.create', ['type' => 'argument', 'id' => $argument->id]) }}" class="ml-2 text-xs text-ink-500 underline">Signaler</a>
                        @endif
                    </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canContribute)
        <form wire:submit="submit" class="mt-4 border-t border-ink-200 pt-4" novalidate>
            @error('cap') <x-alert type="error" class="mb-2">{{ $message }}</x-alert> @enderror
            <label for="body-{{ $sideKey }}" class="mb-1 block text-sm font-medium">Ajouter un argument {{ $sideKey === 'for' ? 'pour' : 'contre' }}</label>
            <textarea id="body-{{ $sideKey }}" wire:model="body" rows="3" maxlength="650" class="block w-full rounded border border-ink-300 px-3 py-2 text-sm" @error('body') aria-invalid="true" @enderror></textarea>
            <p class="mt-1 text-xs text-ink-500">600 caractères maximum.</p>
            @error('body') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            <label for="source-{{ $sideKey }}" class="mb-1 mt-2 block text-sm font-medium">Source (facultative)</label>
            <input id="source-{{ $sideKey }}" type="url" wire:model="source_url" placeholder="https://" inputmode="url" class="block w-full rounded border border-ink-300 px-3 py-2 text-sm">
            @error('source_url') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            <x-button class="mt-3">Publier l'argument</x-button>
        </form>
    @elseif (! auth()->check())
        <p class="mt-4 text-sm text-ink-500"><a href="{{ route('login') }}" class="underline">Connectez-vous</a> pour ajouter un argument.</p>
    @endif
</section>
