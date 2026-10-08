<x-layouts.app title="Signaler un contenu">
    <article class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Signaler {{ $target instanceof \App\Models\Proposal ? 'une proposition' : 'un argument' }}</h1>

        <blockquote class="mt-4 card text-sm">
            @if ($target instanceof \App\Models\Proposal)
                <p class="font-medium">{{ $target->title }}</p>
                <p class="mt-1 text-ink-700">{{ Str::limit($target->measure, 300) }}</p>
            @else
                <p class="text-ink-700">{{ $target->body }}</p>
                <p class="mt-1 text-xs text-ink-700">Argument {{ $target->side->label() }} sur « {{ $target->proposal->title }} »</p>
            @endif
        </blockquote>

        <p class="mt-4 text-sm text-ink-700">Un signalement est examiné par la modération ; la décision, motivée, est inscrite au <a href="{{ route('moderation-log.index') }}" class="link">journal public</a>. Le contenu reste visible jusqu'à la décision, sauf s'il est signalé comme illégal. Votre identité n'est transmise ni à l'auteur ni aux modérateurs. Les motifs sont décrits dans la <a href="{{ route('charter') }}" class="link">charte de modération</a>.</p>

        <form method="POST" action="{{ route('reports.store', ['type' => $type, 'id' => $target->id]) }}" class="mt-6 space-y-5" novalidate>
            @csrf
            @error('cap') <x-alert type="error">{{ $message }}</x-alert> @enderror
            @error('motive') <x-alert type="error">{{ $message }}</x-alert> @enderror

            <fieldset>
                <legend class="text-sm font-medium">Motif <span aria-hidden="true">*</span></legend>
                <div class="mt-2 space-y-2">
                    @foreach ($motives as $motive)
                        <label class="flex items-start gap-3 rounded border border-ink-200 bg-white p-3 text-sm has-[:checked]:border-accent-600">
                            <input type="radio" name="motive" value="{{ $motive->value }}" @checked(old('motive') === $motive->value) required class="mt-0.5 h-4 w-4">
                            <span><span class="font-medium">{{ $motive->label() }}</span><br><span class="text-ink-500">{{ $motive->description() }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="details" class="mb-1.5 block font-bold">Précision (facultative)</label>
                <textarea id="details" name="details" rows="3" maxlength="300" class="field" @error('details') aria-invalid="true" @enderror>{{ old('details') }}</textarea>
                <p class="mt-1 text-xs text-ink-700">300 caractères maximum. Ne mentionnez aucune donnée personnelle.</p>
                @error('details') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <x-button>Envoyer le signalement</x-button>
                <a href="{{ $target->url() }}" class="inline-flex items-center btn btn-secondary">Annuler</a>
            </div>
        </form>
    </article>
</x-layouts.app>
