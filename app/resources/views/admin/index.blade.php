<x-layouts.app title="Administration technique">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Administration technique</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Exploitation seulement : l'administrateur technique n'a aucun pouvoir éditorial ni de modération.</p>

    <dl class="mt-6 grid max-w-md gap-3 text-sm">
        <div><dt class="text-ink-500">Version déployée</dt><dd class="font-medium">{{ $commit ? Str::limit($commit, 12, '') : 'non renseignée' }}</dd></div>
        <div><dt class="text-ink-500">Tâches en file</dt><dd class="font-medium">{{ $queued }}</dd></div>
        <div><dt class="text-ink-500">Tâches en échec</dt><dd class="font-medium">{{ $failed }}</dd></div>
    </dl>

    <section class="mt-8 max-w-2xl card" aria-labelledby="lecture-seule">
        <h2 id="lecture-seule" class="text-xl font-extrabold tracking-tight">Mode lecture seule</h2>
        <p class="mt-2 text-sm text-ink-700">État actuel : <strong>{{ $readOnly ? 'activé' : 'désactivé' }}</strong>. En lecture seule, toute écriture (votes, propositions, arguments, signalements, modération) est refusée ; la lecture et la connexion restent possibles. À utiliser en cas d'attaque ou de maintenance.</p>
        <form method="POST" action="{{ route('admin.read-only') }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="enabled" value="{{ $readOnly ? '0' : '1' }}">
            @unless ($readOnly)
                <div>
                    <label for="message" class="mb-1.5 block font-bold">Message affiché au public (facultatif)</label>
                    <textarea id="message" name="message" rows="3" maxlength="500" class="field" placeholder="{{ \App\Services\ReadOnlyMode::DEFAULT_MESSAGE }}"></textarea>
                </div>
            @endunless
            <x-button :variant="$readOnly ? 'secondary' : 'danger'">{{ $readOnly ? 'Rétablir les écritures' : 'Passer en lecture seule' }}</x-button>
        </form>
    </section>
</x-layouts.app>
