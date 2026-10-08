<x-layouts.app :title="$theme->exists ? 'Modifier le thème' : 'Nouveau thème'">
    <h1 class="text-2xl font-semibold">{{ $theme->exists ? 'Modifier le thème' : 'Nouveau thème' }}</h1>
    <form method="POST" action="{{ $theme->exists ? route('committee.themes.update', $theme) : route('committee.themes.store') }}" class="mt-6 max-w-lg">
        @csrf
        @if ($theme->exists) @method('PUT')@endif

        <x-form.field name="name" label="Nom" required :value="$theme->name" maxlength="80" />
        <x-form.field name="description" label="Description" :value="$theme->description" maxlength="500" help="Une phrase, affichée en tête du thème." />

        <div class="mb-4">
            <label for="parent_id" class="mb-1 block text-sm font-medium">Thème parent</label>
            <select id="parent_id" name="parent_id" class="block w-full rounded border border-ink-300 bg-white px-3 py-2" @error('parent_id') aria-invalid="true" aria-describedby="parent_id-erreur" @enderror>
                <option value="">Aucun (thème principal)</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $theme->parent_id) === $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
            @error('parent_id') <p id="parent_id-erreur" class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label for="status" class="mb-1 block text-sm font-medium">Statut</label>
            <select id="status" name="status" class="block w-full rounded border border-ink-300 bg-white px-3 py-2">
                @foreach (\App\Enums\ThemeStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $theme->status?->value ?? 'open') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
        </div>

        <x-form.field name="position" label="Ordre d'affichage" type="number" :value="$theme->position ?? 0" help="Les thèmes sont triés par ordre croissant, puis par nom." />

        <div class="flex gap-3">
            <x-button>Enregistrer</x-button>
            <a href="{{ route('committee.themes.index') }}" class="rounded border border-ink-300 bg-white px-4 py-2 text-sm hover:bg-ink-100">Annuler</a>
        </div>
    </form>
</x-layouts.app>
