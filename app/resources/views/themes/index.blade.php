<x-layouts.app title="Thèmes">
    <div class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Thèmes</h1>
        <p class="mt-3 text-lg text-ink-700">Chaque thème rassemble des mesures concrètes, à lire, débattre, voter et arbitrer. Plusieurs lectures par thème, jamais un palmarès.</p>
    </div>

    @if ($themes->isEmpty())
        <div class="mt-10 flex flex-col items-center gap-4 text-center">
            <x-illustration name="empty" />
            <p class="text-ink-500">Aucun thème ouvert pour l'instant.</p>
        </div>
    @endif

    <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($themes as $theme)
            @php
                $tone = ['card-lagoon', 'card-sand', 'card-plum', 'card-flat'][$loop->index % 4];
                $iconBg = ['bg-accent-600', 'bg-ink-900', 'bg-plum-600', 'bg-accent-600'][$loop->index % 4];
            @endphp
            <li class="{{ $tone }} card-lift rise relative flex flex-col gap-4">
                <span class="inline-flex size-12 items-center justify-center rounded-2xl text-white {{ $iconBg }}" aria-hidden="true"><x-icon :name="$theme->icon?->value ?? 'grid'" class="size-6" /></span>
                <h2 class="text-2xl leading-tight font-extrabold tracking-tight">
                    <a href="{{ route('themes.show', $theme) }}" class="text-ink-900 no-underline after:absolute after:inset-0 hover:underline">{{ $theme->name }}</a>
                </h2>
                @if ($theme->status === \App\Enums\ThemeStatus::Closed)
                    <x-pill tone="outline" class="self-start">fermé aux nouvelles propositions</x-pill>
                @endif
                @if ($theme->description)
                    <p class="text-ink-700">{{ $theme->description }}</p>
                @endif
                <p class="mt-auto text-sm font-semibold text-ink-700">{{ trans_choice(':count proposition|:count propositions', $theme->proposals_count) }}</p>
                @if ($theme->children->isNotEmpty())
                    <ul class="relative z-10 flex flex-wrap gap-2 text-sm">
                        @foreach ($theme->children as $child)
                            <li><a href="{{ route('themes.show', $child) }}" class="pill border-2 border-ink-200 bg-white text-ink-900 no-underline hover:border-ink-900">{{ $child->name }} ({{ $child->proposals_count }})</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</x-layouts.app>
