<section class="card" aria-labelledby="arguments-{{ $sideKey }}">
    <h2 id="arguments-{{ $sideKey }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight">
        <span class="inline-flex size-8 items-center justify-center rounded-lg bg-ink-900 text-white" aria-hidden="true"><x-icon :name="$sideKey === 'for' ? 'plus' : 'minus'" class="size-4" stroke="3" /></span>
        {{ $sideEnum->label() }} <span class="text-sm font-semibold text-ink-700">({{ $publishedCount }})</span>
    </h2>

    @include('partials.celebrations', ['milestones' => array_map(fn (string $k) => \App\Enums\Milestone::from($k), $celebrations)])

    @if ($arguments->isEmpty())
        <p class="mt-4 text-sm text-ink-700">Aucun argument {{ $sideKey === 'for' ? 'pour' : 'contre' }} pour l'instant.</p>
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($arguments as $argument)
                <li class="rounded-2xl bg-ink-50 p-4" id="argument-{{ $argument->id }}" wire:key="argument-{{ $argument->id }}">
                    @if ($argument->isHidden())
                        <p class="text-sm text-ink-700">Argument masqué par la modération @if ($argument->hidden_motive && ! $argument->hidesEverything()) ({{ $argument->hidden_motive->label() }})@endif · <a href="{{ route('moderation-log.index', ['type' => 'argument']) }}" class="link">journal</a></p>
                    @else
                    <p class="leading-relaxed">{{ $argument->body }}</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm text-ink-700">
                        <p>
                            {{ $argument->authorName() }} · {{ $argument->created_at?->translatedFormat('j F Y') }}
                            @if ($argument->source_url)
                                · <a href="{{ $argument->source_url }}" rel="noopener nofollow" class="link">source</a>
                            @endif
                            @if ($canReport && $argument->author_id !== auth()->id())
                                · <a href="{{ route('reports.create', ['type' => 'argument', 'id' => $argument->id]) }}" class="text-ink-500 underline">Signaler</a>
                            @endif
                        </p>
                        @if ($canContribute)
                            <button type="button" wire:click="toggleMark({{ $argument->id }})" aria-pressed="{{ in_array($argument->id, $marked, true) ? 'true' : 'false' }}"
                                class="pill min-h-9 border-2 text-sm transition-colors {{ in_array($argument->id, $marked, true) ? 'border-plum-600 bg-plum-100 text-plum-700' : 'border-ink-200 bg-white text-ink-900 hover:border-plum-600' }}">
                                <x-icon name="star" class="size-4" /> Utile · {{ $argument->marked_by_count }}
                            </button>
                        @else
                            <span class="pill border-2 border-ink-200 bg-white text-sm text-ink-700"><x-icon name="star" class="size-4" /> Utile · {{ $argument->marked_by_count }}</span>
                        @endif
                    </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canContribute)
        <form wire:submit="submit" class="mt-5 border-t border-ink-100 pt-5" novalidate>
            @error('cap') <x-alert type="error" class="mb-3">{{ $message }}</x-alert> @enderror
            <label for="body-{{ $sideKey }}" class="mb-1.5 block font-bold">Ajouter un argument {{ $sideKey === 'for' ? 'pour' : 'contre' }}</label>
            <textarea id="body-{{ $sideKey }}" wire:model="body" rows="3" maxlength="650" class="field" @error('body') aria-invalid="true" @enderror></textarea>
            <p class="mt-1 text-xs text-ink-700">600 caractères maximum.</p>
            @error('body') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            <label for="source-{{ $sideKey }}" class="mt-3 mb-1.5 block font-bold">Source (facultative)</label>
            <input id="source-{{ $sideKey }}" type="url" wire:model="source_url" placeholder="https://" inputmode="url" class="field">
            @error('source_url') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            <x-button class="mt-4"><x-icon name="pen" class="size-4" /> Publier l'argument</x-button>
        </form>
    @elseif (! auth()->check())
        <p class="mt-4 text-sm text-ink-700"><a href="{{ route('login') }}" class="link">Connectez-vous</a> pour ajouter un argument.</p>
    @endif
</section>
