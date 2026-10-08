<x-layouts.app title="Examiner une contestation">
    @php($entry = $appeal->logEntry)
    <p class="text-sm"><a href="{{ route('moderation.appeals.index') }}" class="link">← Contestations</a></p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Contestation n°{{ $appeal->id }}</h1>

    @error('appeal') <x-alert type="error" class="mt-4">{{ $message }}</x-alert> @enderror
    @error('decision') <x-alert type="error" class="mt-4">{{ $message }}</x-alert> @enderror

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <h2 class="eyebrow text-ink-700">Décision contestée</h2>
                <p class="mt-2 text-sm">{{ $entry->action->label() }}@if ($entry->motive) · {{ $entry->motive->label() }}@endif · {{ $entry->created_at->translatedFormat('j F Y à H\hi') }} · {{ $entry->actor_role->label() }} · <a href="{{ $entry->url() }}" class="link">journal</a></p>
            </section>

            <section class="card">
                <h2 class="eyebrow text-ink-700">Contenu concerné</h2>
                @if ($entry->target_type === 'user')
                    <p class="mt-2 text-sm">Compte participant « {{ $target?->pseudonym ?? 'supprimé' }} »@if (isset($entry->details['days'])), suspendu {{ $entry->details['days'] }} jours @endif.</p>
                @elseif ($target instanceof \App\Models\Proposal)
                    <p class="mt-2 text-lg font-medium">{{ $target->title }}</p>
                    <p class="mt-1 text-xs text-ink-700">Thème : {{ $target->theme->fullName() }} · auteur : {{ $target->authorName() }}</p>
                    <h3 class="mt-4 eyebrow text-ink-700">Problème</h3>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $target->problem }}</p>
                    <h3 class="mt-4 eyebrow text-ink-700">Mesure</h3>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $target->measure }}</p>
                @elseif ($target instanceof \App\Models\Argument)
                    <p class="mt-2 whitespace-pre-line text-sm">{{ $target->body }}</p>
                    <p class="mt-1 text-xs text-ink-700">auteur : {{ $target->authorName() }}</p>
                @else
                    <p class="mt-2 text-sm text-ink-700">Contenu supprimé.</p>
                @endif
            </section>

            <section class="card">
                <h2 class="eyebrow text-ink-700">Contestation de l'auteur</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $appeal->body }}</p>
                <p class="mt-1 text-xs text-ink-700">Déposée le {{ $appeal->created_at?->translatedFormat('j F Y à H\hi') }}</p>
            </section>
        </div>

        <aside>
            <section class="card">
                <h2 class="eyebrow text-ink-700">Trancher</h2>
                @if (! $appeal->isPending())
                    <p class="mt-2 text-sm">{{ $appeal->status->label() }}</p>
                @elseif ($entry->actor_id === auth()->id())
                    <x-alert type="info" class="mt-2">Vous avez pris la décision contestée : un autre membre du comité doit trancher.</x-alert>
                @else
                    <form method="POST" action="{{ route('moderation.appeals.decide', $appeal) }}" class="mt-3 space-y-3">
                        @csrf
                        <fieldset>
                            <legend class="sr-only">Décision</legend>
                            <label class="flex items-start gap-2 text-sm"><input type="radio" name="decision" value="confirm" required class="mt-1"> <span><strong>Confirmer</strong> la décision</span></label>
                            <label class="mt-2 flex items-start gap-2 text-sm"><input type="radio" name="decision" value="overturn" class="mt-1"> <span><strong>Annuler</strong> la décision et rétablir le contenu ou le compte</span></label>
                        </fieldset>
                        <div>
                            <label for="decision_note" class="mb-1.5 block font-bold">Motivation (visible de l'auteur)</label>
                            <textarea id="decision_note" name="decision_note" rows="4" maxlength="500" class="field">{{ old('decision_note') }}</textarea>
                            @error('decision_note') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                        </div>
                        <x-button class="w-full">Enregistrer la décision</x-button>
                    </form>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.app>
