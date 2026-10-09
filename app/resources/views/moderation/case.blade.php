<x-layouts.app title="Dossier de modération">
    @php($isProposal = $target instanceof \App\Models\Proposal)
    <p class="text-sm"><a href="{{ route('moderation.queue') }}" class="link">← File de modération</a></p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Dossier · {{ $isProposal ? 'proposition' : 'argument' }} n°{{ $target->id }}</h1>

    @if ($target->isModerated())
        <x-alert type="info" class="mt-4">Statut actuel : {{ $isProposal && $target->awaitsRewrite() ? 'reformulation demandée' : 'masqué' }}@if ($target->hidden_motive) ({{ $target->hidden_motive->label() }})@endif.</x-alert>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card" aria-labelledby="contenu-signale">
                <h2 id="contenu-signale" class="eyebrow text-ink-700">Contenu</h2>
                @if ($isProposal)
                    <p class="mt-2 text-lg font-medium">{{ $target->title }}</p>
                    <p class="mt-1 text-xs text-ink-700">Thème : {{ $target->theme->fullName() }} · <a href="{{ $target->url() }}" class="link">voir la fiche</a></p>
                    <h3 class="mt-4 eyebrow text-ink-700">Problème</h3>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $target->problem }}</p>
                    <h3 class="mt-4 eyebrow text-ink-700">Mesure</h3>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $target->measure }}</p>
                    <h3 class="mt-4 eyebrow text-ink-700">Sources</h3>
                    <ul class="mt-1 text-sm">
                        @foreach ($target->sources as $source)
                            <li>{{ $source->is_personal ? 'Proposition personnelle' : $source->url }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-xs text-ink-700">Argument {{ $target->side->label() }} sur <a href="{{ $target->proposal->url() }}" class="link">{{ $target->proposal->title }}</a></p>
                    <p class="mt-2 whitespace-pre-line text-sm">{{ $target->body }}</p>
                    @if ($target->source_url) <p class="mt-1 text-xs text-ink-700">Source : {{ $target->source_url }}</p> @endif
                @endif
            </section>

            <section class="card" aria-labelledby="signalements">
                <h2 id="signalements" class="eyebrow text-ink-700">Signalements en attente ({{ $openReports->count() }})</h2>
                <ul class="mt-2 divide-y divide-ink-200 text-sm">
                    @foreach ($openReports as $report)
                        @php($h = $reporterHistories[$report->id])
                        <li class="py-3">
                            <p><span class="font-medium">{{ $report->motive->label() }}</span> · {{ $report->created_at?->translatedFormat('j F Y à H\hi') }}</p>
                            @if ($report->details) <p class="mt-1 text-ink-700">« {{ $report->details }} »</p> @endif
                            <p class="mt-1 text-xs text-ink-700">Signaleur : {{ $h['emitted'] }} {{ Str::plural('signalement', $h['emitted']) }} au total, {{ $h['upheld'] }} sur {{ $h['handled'] }} {{ Str::plural('retenu', $h['upheld']) }} parmi ceux déjà traités.</p>
                        </li>
                    @endforeach
                </ul>
                @if ($pastReports->isNotEmpty())
                    <p class="mt-3 text-xs text-ink-700">{{ $pastReports->count() }} {{ Str::plural('signalement', $pastReports->count()) }} déjà {{ Str::plural('traité', $pastReports->count()) }} sur ce contenu.</p>
                @endif
            </section>

            @if ($entries->isNotEmpty())
                <section class="card" aria-labelledby="decisions">
                    <h2 id="decisions" class="eyebrow text-ink-700">Décisions antérieures sur ce contenu</h2>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($entries as $entry)
                            <li>{{ $entry->created_at->translatedFormat('j F Y à H\hi') }} · {{ $entry->action->label() }}@if ($entry->motive) · {{ $entry->motive->label() }}@endif · {{ $entry->actor_role->label() }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="card" aria-labelledby="auteur">
                <h2 id="auteur" class="eyebrow text-ink-700">Auteur</h2>
                <p class="mt-2 text-sm">{{ $author['pseudonym'] }}</p>
                @if ($author['account_age_days'] !== null)
                    <p class="text-xs text-ink-700">Compte créé il y a {{ $author['account_age_days'] }} {{ Str::plural('jour', $author['account_age_days']) }} · {{ $author['prior_sanctions'] }} {{ Str::plural('décision', $author['prior_sanctions']) }} de masquage ou de reformulation antérieure{{ $author['prior_sanctions'] > 1 ? 's' : '' }}</p>
                @endif
            </section>

            <section class="card space-y-5" aria-labelledby="actions">
                <h2 id="actions" class="eyebrow text-ink-700">Décision</h2>
                @error('motive') <x-alert type="error">{{ $message }}</x-alert> @enderror
                @error('kept_proposal_id') <x-alert type="error">{{ $message }}</x-alert> @enderror
                @error('action') <x-alert type="error">{{ $message }}</x-alert> @enderror

                <form method="POST" action="{{ route('moderation.case.keep', ['type' => $type, 'id' => $target->id]) }}">
                    @csrf
                    <x-button variant="secondary" class="w-full">Conserver{{ $target->isHidden() ? ' et rendre visible' : '' }}</x-button>
                    <p class="mt-1 text-xs text-ink-700">Le contenu respecte la charte. Les signalements sont clos.</p>
                </form>

                <form method="POST" action="{{ route('moderation.case.hide', ['type' => $type, 'id' => $target->id]) }}" class="border-t border-ink-200 pt-4">
                    @csrf
                    <label for="hide-motive" class="mb-1.5 block font-bold">Masquer avec motif</label>
                    <select id="hide-motive" name="motive" required class="field">
                        <option value="">— Motif —</option>
                        @foreach ($motives as $motive)
                            <option value="{{ $motive->value }}" @selected(old('motive') === $motive->value)>{{ $motive->label() }}</option>
                        @endforeach
                    </select>
                    @if ($isProposal)
                        <label for="kept" class="mb-1 mt-2 block text-sm font-medium">Si doublon : numéro de la fiche conservée</label>
                        <input id="kept" name="kept_proposal_id" type="number" inputmode="numeric" min="1" value="{{ old('kept_proposal_id') }}" class="field">
                    @endif
                    <x-button variant="danger" class="mt-3 w-full">Masquer</x-button>
                </form>

                @can('arbitrate-appeals')
                    @if ($target->author && ! $target->author->role->isPrivileged())
                        <form method="POST" action="{{ route('moderation.case.suspend', ['type' => $type, 'id' => $target->id]) }}" class="border-t border-ink-200 pt-4">
                            @csrf
                            @error('suspend') <x-alert type="error" class="mb-2">{{ $message }}</x-alert> @enderror
                            @error('days') <x-alert type="error" class="mb-2">{{ $message }}</x-alert> @enderror
                            <label for="suspend-motive" class="mb-1.5 block font-bold">Suspendre le compte de l'auteur (comité)</label>
                            @if ($target->author->isSuspended())
                                <p class="text-xs text-ink-700">Ce compte est déjà suspendu.</p>
                            @else
                                <select id="suspend-motive" name="motive" required class="field">
                                    <option value="">— Motif —</option>
                                    @foreach ($motives as $motive)
                                        <option value="{{ $motive->value }}">{{ $motive->label() }}</option>
                                    @endforeach
                                </select>
                                <label for="suspend-days" class="mb-1 mt-2 block text-sm font-medium">Durée en jours (vide : sans terme)</label>
                                <input id="suspend-days" name="days" type="number" inputmode="numeric" min="1" max="3650" class="field">
                                <x-button variant="danger" class="mt-3 w-full">Suspendre le compte</x-button>
                            @endif
                        </form>
                    @endif
                @endcan

                @if ($isProposal && $target->author_id !== null)
                    <form method="POST" action="{{ route('moderation.case.rewrite', ['type' => $type, 'id' => $target->id]) }}" class="border-t border-ink-200 pt-4">
                        @csrf
                        <label for="rewrite-motive" class="mb-1.5 block font-bold">Demander une reformulation</label>
                        <select id="rewrite-motive" name="motive" required class="field">
                            <option value="">— Motif —</option>
                            @foreach ($motives as $motive)
                                <option value="{{ $motive->value }}">{{ $motive->label() }}</option>
                            @endforeach
                        </select>
                        <x-button variant="secondary" class="mt-3 w-full">Demander une reformulation</x-button>
                        <p class="mt-1 text-xs text-ink-700">La fiche devient invisible ; l'auteur peut en modifier le fond une fois sous {{ config('votalis.moderation.rewrite_days') }} jours.</p>
                    </form>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.app>
