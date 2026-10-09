<x-layouts.app :title="$proposal->title">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink-700">
        <a href="{{ route('themes.index') }}" class="hover:underline">Thèmes</a>
        @if ($proposal->theme->parent)
            <x-icon name="chevron-right" class="size-4" /><a href="{{ route('themes.show', $proposal->theme->parent) }}" class="hover:underline">{{ $proposal->theme->parent->name }}</a>
        @endif
        <x-icon name="chevron-right" class="size-4" /><a href="{{ route('themes.show', $proposal->theme) }}" class="hover:underline">{{ $proposal->theme->name }}</a>
    </nav>

    @if ($moderationEntry ?? null)
        <x-alert type="info" class="mt-5">
            <span>
                @if ($proposal->awaitsRewrite())
                    Reformulation demandée par la modération @if ($proposal->hidden_motive) ({{ $proposal->hidden_motive->label() }})@endif : cette fiche est invisible du public jusqu'à sa reformulation @if ($proposal->rewrite_allowed_until), possible jusqu'au {{ $proposal->rewrite_allowed_until->translatedFormat('j F Y') }}@endif.
                @else
                    Cette fiche est masquée par la modération @if ($proposal->hidden_motive) ({{ $proposal->hidden_motive->label() }})@endif et n'est visible que de son auteur et de la modération.
                @endif
                <a href="{{ $moderationEntry->url() }}" class="link">Voir la décision au journal public</a>.
            </span>
        </x-alert>
    @endif

    <article class="mt-5">
        <header class="rise">
            <h1 class="text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $proposal->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-pill tone="lagoon">{{ $proposal->origin->label() }}</x-pill>
                <x-pill tone="sand">{{ $proposal->theme->fullName() }}</x-pill>
                @if ($proposal->seed_source)
                    <x-pill tone="outline">{{ $proposal->seed_source }}</x-pill>
                @endif
            </div>
            <p class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm text-ink-700">
                <span>Proposée par <strong>{{ $proposal->authorName() }}</strong></span>
                <span>publiée le {{ $proposal->created_at?->translatedFormat('j F Y') }}</span>
                @if ($proposal->revisions->count() > 1)
                    <span>modifiée le {{ $proposal->revisions->first()?->created_at->translatedFormat('j F Y') }}</span>
                @endif
                @can('update', $proposal)
                    <a href="{{ route('proposals.edit', $proposal) }}" class="link">{{ $proposal->awaitsRewrite() ? 'Reformuler' : ($proposal->isLocked() ? 'Corriger la forme' : 'Modifier') }}</a>
                @endcan
                @can('report', $proposal)
                    <a href="{{ route('reports.create', ['type' => 'proposition', 'id' => $proposal->id]) }}" class="text-ink-500 underline">Signaler</a>
                @endcan
            </p>
        </header>

        <div class="mt-8 grid gap-5 lg:grid-cols-12">
            <div class="flex min-w-0 flex-col gap-5 lg:col-span-7">
                <x-card as="section" aria-labelledby="probleme" class="rise">
                    <h2 id="probleme" class="eyebrow flex items-center gap-2 text-accent-700"><span class="size-3 rounded-full bg-accent-600" aria-hidden="true"></span>Problème visé</h2>
                    <p class="mt-3 text-lg leading-relaxed whitespace-pre-line">{{ $proposal->problem }}</p>
                </x-card>
                <x-card as="section" aria-labelledby="mesure" class="rise rise-2">
                    <h2 id="mesure" class="eyebrow flex items-center gap-2 text-accent-700"><span class="size-3 rounded-full bg-accent-600" aria-hidden="true"></span>Mesure proposée</h2>
                    <p class="mt-3 text-lg leading-relaxed whitespace-pre-line">{{ $proposal->measure }}</p>
                </x-card>
            </div>
            <aside class="flex min-w-0 flex-col gap-5 lg:col-span-5">
                <x-card tone="sand" as="section" aria-labelledby="cout" class="rise rise-2">
                    <h2 id="cout" class="eyebrow flex items-center gap-2 text-plum-700"><span class="size-3 rounded-full bg-plum-600" aria-hidden="true"></span>Coût ou impact estimé</h2>
                    <p class="mt-3 text-2xl leading-snug font-extrabold tracking-tight">{{ $proposal->cost_unknown ? 'Inconnu' : $proposal->cost_estimate }}</p>
                </x-card>
                <x-card as="section" aria-labelledby="sources" class="rise rise-3">
                    <h2 id="sources" class="eyebrow flex items-center gap-2 text-ink-700"><span class="size-3 rounded-full bg-ink-900" aria-hidden="true"></span>Sources</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($proposal->sources as $source)
                            <li class="flex gap-2">
                                <x-icon name="link" class="mt-0.5 size-4 text-ink-500" />
                                @if ($source->is_personal)
                                    <span>Proposition personnelle</span>
                                @else
                                    @php $host = preg_replace('/^www\./', '', (string) parse_url((string) $source->url, PHP_URL_HOST)); @endphp
                                    <span class="block min-w-0 flex-1">
                                        <a href="{{ $source->url }}" rel="noopener nofollow" class="link">{{ $source->label ?? $host }}</a>
                                        @if ($source->label === null)
                                            <span class="block truncate text-xs text-ink-700" title="{{ $source->url }}">{{ Str::limit((string) parse_url((string) $source->url, PHP_URL_PATH), 70) }}</span>
                                        @endif
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-card>
                <details class="card-flat rise rise-4 group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 font-bold [&::-webkit-details-marker]:hidden">
                        Historique des modifications
                        <x-icon name="chevron-down" class="size-5 transition-transform group-open:rotate-180" />
                    </summary>
                    <ol class="mt-3 space-y-1.5 text-sm text-ink-700">
                        @foreach ($proposal->revisions as $revision)
                            <li>
                                {{ $revision->created_at->translatedFormat('j F Y à H\hi') }} ·
                                {{ $loop->last ? 'Publication' : $revision->kind->label() }} ·
                                {{ $revision->author?->pseudonym ?? ($proposal->origin === \App\Enums\ProposalOrigin::Seed ? 'Comité éditorial' : 'Participant supprimé') }}
                            </li>
                        @endforeach
                    </ol>
                </details>
            </aside>
        </div>

        <div class="mt-8">
            <livewire:vote-box :proposal="$proposal" :key="'vote-'.$proposal->id" />
        </div>

        <section class="mt-10" aria-labelledby="arguments">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="arguments" class="text-2xl font-extrabold tracking-tight sm:text-3xl">Les arguments</h2>
                <p class="text-sm text-ink-700">Même traitement pour les deux camps. « Utile » n'est pas un vote.</p>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::For" :key="'for-'.$proposal->id" />
                <livewire:argument-column :proposal="$proposal" :side="\App\Enums\ArgumentSide::Against" :key="'against-'.$proposal->id" />
            </div>
        </section>
    </article>
</x-layouts.app>
