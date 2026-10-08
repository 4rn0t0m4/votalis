<x-layouts.app :title="$proposal->title">
    <nav aria-label="Fil d'Ariane" class="text-sm text-ink-500">
        <a href="{{ route('themes.index') }}" class="hover:underline">Thèmes</a>
        @if ($proposal->theme->parent)
            › <a href="{{ route('themes.show', $proposal->theme->parent) }}" class="hover:underline">{{ $proposal->theme->parent->name }}</a>
        @endif
        › <a href="{{ route('themes.show', $proposal->theme) }}" class="hover:underline">{{ $proposal->theme->name }}</a>
    </nav>

    <article class="mt-3">
        <header>
            <h1 class="text-2xl font-semibold">{{ $proposal->title }}</h1>
            <p class="mt-2 text-sm text-ink-500">
                {{ $proposal->origin->label() }}@if ($proposal->seed_source) ({{ $proposal->seed_source }})@endif
                · {{ $proposal->authorName() }}
                · publiée le {{ $proposal->created_at?->translatedFormat('j F Y') }}
                @if ($proposal->revisions->count() > 1)
                    · modifiée le {{ $proposal->revisions->first()?->created_at->translatedFormat('j F Y') }}
                @endif
            </p>
            @can('update', $proposal)
                <p class="mt-2"><a href="{{ route('proposals.edit', $proposal) }}" class="text-sm underline">{{ $proposal->isLocked() ? 'Corriger la forme' : 'Modifier' }}</a></p>
            @endcan
        </header>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="probleme">
                    <h2 id="probleme" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Problème visé</h2>
                    <p class="mt-2 whitespace-pre-line">{{ $proposal->problem }}</p>
                </section>
                <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="mesure">
                    <h2 id="mesure" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Mesure proposée</h2>
                    <p class="mt-2 whitespace-pre-line">{{ $proposal->measure }}</p>
                </section>
            </div>
            <aside class="space-y-6">
                <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="cout">
                    <h2 id="cout" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Coût ou impact estimé</h2>
                    <p class="mt-2 text-sm">{{ $proposal->cost_unknown ? 'Inconnu' : $proposal->cost_estimate }}</p>
                </section>
                <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="sources">
                    <h2 id="sources" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Sources</h2>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($proposal->sources as $source)
                            <li>
                                @if ($source->is_personal)
                                    Proposition personnelle
                                @else
                                    <a href="{{ $source->url }}" rel="noopener nofollow" class="break-all underline">{{ $source->label ?? $source->url }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
                <section class="rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="historique">
                    <h2 id="historique" class="text-sm font-semibold uppercase tracking-wide text-ink-500">Historique des modifications</h2>
                    <ol class="mt-2 space-y-1 text-sm">
                        @foreach ($proposal->revisions as $revision)
                            <li>
                                {{ $revision->created_at->translatedFormat('j F Y à H\hi') }} ·
                                {{ $loop->last ? 'Publication' : $revision->kind->label() }} ·
                                {{ $revision->author?->pseudonym ?? ($proposal->origin === \App\Enums\ProposalOrigin::Seed ? 'Comité éditorial' : 'Participant supprimé') }}
                            </li>
                        @endforeach
                    </ol>
                </section>
            </aside>
        </div>

        <div class="mt-8">
            <livewire:vote-box :proposal="$proposal" :key="'vote-'.$proposal->id" />
        </div>

        <section class="mt-8" aria-labelledby="arguments">
            <h2 id="arguments" class="sr-only">Arguments</h2>
            <div class="grid gap-6 md:grid-cols-2">
                <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::For" :key="'for-'.$proposal->id" />
                <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::Against" :key="'against-'.$proposal->id" />
            </div>
        </section>
    </article>
</x-layouts.app>
