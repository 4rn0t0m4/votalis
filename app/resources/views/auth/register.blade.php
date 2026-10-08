<x-layouts.app title="Créer un compte">
    <x-auth-card title="Créer un compte">
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <x-form.field name="pseudonym" label="Pseudonyme" required autocomplete="username" maxlength="40"
                help="De 3 à 40 caractères : lettres, chiffres, tirets et traits de soulignement. C'est le seul nom visible des autres participants." />
            <x-form.field name="email" label="Adresse e-mail" type="email" required autocomplete="email"
                help="Sert uniquement à vérifier votre compte et à vous écrire. Elle est chiffrée et jamais affichée." />
            <x-form.field name="password" label="Mot de passe" type="password" required autocomplete="new-password"
                help="12 caractères minimum. Les mots de passe présents dans des fuites connues sont refusés." />
            <x-form.field name="password_confirmation" label="Confirmer le mot de passe" type="password" required autocomplete="new-password" />

            <div class="mb-4">
                <div class="flex items-start gap-2">
                    <input id="consent" name="consent" type="checkbox" value="1" required aria-required="true" @checked(old('consent'))
                        class="mt-1 h-4 w-4 rounded border-ink-300" @error('consent') aria-invalid="true" aria-describedby="consent-erreur" @enderror>
                    <label for="consent" class="text-sm">
                        J'accepte que mes votes et contributions, qui peuvent révéler des opinions politiques, soient traités
                        pour faire fonctionner la plateforme, dans les conditions de la
                        <a href="#" class="underline">politique de confidentialité</a>. Ils sont liés à un identifiant interne, jamais à mon e-mail.
                    </label>
                </div>
                @error('consent')
                    <p id="consent-erreur" class="mt-1 text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>

            <x-button class="w-full">Créer mon compte</x-button>
        </form>

        <x-slot:footer>
            <p>Déjà un compte ? <a href="{{ route('login') }}" class="underline">Se connecter</a></p>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
