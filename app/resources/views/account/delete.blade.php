<x-layouts.app title="Supprimer mon compte">
    <p class="text-sm"><a href="{{ route('account.data.show') }}" class="underline">← Mes données</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Supprimer mon compte</h1>

    <x-alert type="error" class="mt-4 max-w-2xl">Cette action est immédiate et irréversible.</x-alert>

    <ul class="mt-4 max-w-2xl list-disc space-y-1 pl-5 text-sm text-ink-700">
        <li>Effacés : votre e-mail, votre mot de passe, vos clés d'accès, vos votes et conditions, vos réponses aux arbitrages, vos marques « utile ».</li>
        <li>Conservés sans votre nom : vos propositions et arguments publiés (« participant supprimé »), vos signalements et contestations sans texte libre, les décisions de modération du journal public.</li>
        <li>Vous pouvez d'abord <a href="{{ route('account.data.show') }}" class="underline">exporter vos données</a>.</li>
    </ul>

    @if (auth()->user()->role->isPrivileged())
        <x-alert type="info" class="mt-6 max-w-2xl">Votre compte a un rôle privilégié. Demandez d'abord à l'administrateur technique de le ramener au rôle participant.</x-alert>
    @else
        <form method="POST" action="{{ route('account.data.destroy') }}" class="mt-6 max-w-md space-y-4" novalidate>
            @csrf
            @method('DELETE')
            @error('account') <x-alert type="error">{{ $message }}</x-alert> @enderror
            <x-form.field name="current_password" label="Votre mot de passe" type="password" required autocomplete="current-password" />
            <div class="flex items-start gap-2">
                <input id="confirmation" name="confirmation" type="checkbox" value="1" required class="mt-1 h-4 w-4 rounded border-ink-300" @error('confirmation') aria-invalid="true" @enderror>
                <label for="confirmation" class="text-sm">Je comprends que mon compte et mes votes seront supprimés définitivement.</label>
            </div>
            @error('confirmation') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
            <x-button variant="danger">Supprimer définitivement mon compte</x-button>
        </form>
    @endif
</x-layouts.app>
