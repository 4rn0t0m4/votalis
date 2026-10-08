<x-layouts.app title="Thèmes">
    <h1 class="text-2xl font-semibold">Thèmes</h1>
    <p class="mt-2 text-ink-700">Chaque thème rassemble des mesures concrètes, à lire, débattre et bientôt voter.</p>

    @if ($themes->isEmpty())
        <p class="mt-6 text-ink-500">Aucun thème ouvert pour l'instant.</p>
    @endif

    <ul class="mt-6 grid gap-4 sm:grid-cols-2">
        @foreach ($themes as $theme)
            <li class="rounded-lg border border-ink-200 bg-white p-4">
                <h2 class="text-lg font-semibold">
                    <a href="{{ route('themes.show', $theme) }}" class="hover:underline">{{ $theme->name }}</a>
                    @if ($theme->status === \App\Enums\ThemeStatus::Closed)
                        <span class="ml-2 rounded bg-ink-100 px-2 py-0.5 text-xs font-normal text-ink-700">fermé aux nouvelles propositions</span>
                    @endif
                </h2>
                @if ($theme->description)
                    <p class="mt-1 text-sm text-ink-700">{{ $theme->description }}</p>
                @endif
                <p class="mt-2 text-sm text-ink-500">{{ trans_choice(':count proposition|:count propositions', $theme->proposals_count) }}</p>
                @if ($theme->children->isNotEmpty())
                    <ul class="mt-2 flex flex-wrap gap-2 text-sm">
                        @foreach ($theme->children as $child)
                            <li><a href="{{ route('themes.show', $child) }}" class="rounded border border-ink-300 px-2 py-0.5 hover:bg-ink-100">{{ $child->name }} ({{ $child->proposals_count }})</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</x-layouts.app>
