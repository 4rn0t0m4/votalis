<x-layouts.app title="Contester une décision">
    @php($target = $entry->target)
    <p class="text-sm"><a href="{{ route('account.moderation.index') }}" class="link">← Décisions me concernant</a></p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Contester une décision</h1>

    <dl class="mt-4 max-w-2xl card text-sm">
        <div class="flex gap-3"><dt class="text-ink-500">Décision</dt><dd>{{ $entry->action->label() }}@if ($entry->motive) · {{ $entry->motive->label() }}@endif · {{ $entry->created_at->translatedFormat('j F Y') }}</dd></div>
        <div class="mt-2 flex gap-3"><dt class="text-ink-500">Concerne</dt>
            <dd>
                @if ($entry->target_type === 'user') Votre compte
                @elseif ($target instanceof \App\Models\Proposal) la proposition « {{ $target->title }} »
                @elseif ($target instanceof \App\Models\Argument) l'argument « {{ Str::limit($target->body, 120) }} »
                @endif
            </dd>
        </div>
    </dl>

    <p class="mt-4 max-w-2xl text-sm text-ink-700">Vous ne pouvez contester qu'une fois. Expliquez en quoi la décision ne respecte pas la <a href="{{ route('charter') }}" class="link">charte</a>. Le comité éditorial tranchera ; le membre à l'origine de la décision ne participera pas. L'issue sera inscrite au journal public, sans votre texte.</p>

    <form method="POST" action="{{ route('account.moderation.appeal.store', $entry) }}" class="mt-6 max-w-2xl space-y-4" novalidate>
        @csrf
        <div>
            <label for="body" class="mb-1.5 block font-bold">Votre contestation <span aria-hidden="true">*</span></label>
            <textarea id="body" name="body" rows="6" minlength="20" maxlength="1000" required class="field" @error('body') aria-invalid="true" @enderror>{{ old('body') }}</textarea>
            <p class="mt-1 text-xs text-ink-700">Entre 20 et 1 000 caractères. N'y mettez aucune donnée personnelle.</p>
            @error('body') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
        </div>
        <x-button>Envoyer la contestation</x-button>
    </form>
</x-layouts.app>
