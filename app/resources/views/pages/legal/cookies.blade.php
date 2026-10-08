<x-layouts.app title="Politique cookies">
    <article class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Politique cookies</h1>
        <p class="mt-4 text-ink-700">La plateforme n'utilise que des cookies strictement nécessaires à son fonctionnement. Ils sont exemptés de consentement au sens des lignes directrices de la CNIL : c'est pourquoi aucune bannière ne vous est présentée.</p>

        <table class="mt-6 w-full text-sm">
            <thead class="text-left text-ink-700"><tr><th class="py-2">Cookie</th><th class="py-2">Rôle</th><th class="py-2">Durée</th></tr></thead>
            <tbody class="divide-y divide-ink-200">
                <tr><td class="py-2"><code>{{ config('session.cookie') }}</code></td><td class="py-2">Session : vous garder connecté d'une page à l'autre</td><td class="py-2">{{ config('session.lifetime') }} minutes d'inactivité</td></tr>
                <tr><td class="py-2"><code>XSRF-TOKEN</code></td><td class="py-2">Protection contre les requêtes forgées</td><td class="py-2">Session</td></tr>
                <tr><td class="py-2"><code>remember_web_…</code></td><td class="py-2">« Se souvenir de moi », seulement si vous le cochez</td><td class="py-2">Jusqu'à la déconnexion</td></tr>
            </tbody>
        </table>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Ce que nous n'utilisons pas</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Aucun cookie publicitaire, de réseau social ou de suivi entre sites.</li>
            <li>Aucune mesure d'audience tierce. Si une mesure d'audience est un jour ajoutée, elle sera auto-hébergée, configurée en mode exempté de consentement selon la CNIL (pas de suivi individuel), et annoncée ici.</li>
            <li>Aucune ressource chargée depuis un service tiers : polices, scripts et styles sont servis par la plateforme.</li>
        </ul>

        <p class="mt-8 text-sm text-ink-700">Tous les cookies sont marqués <code>Secure</code>, <code>HttpOnly</code> (sauf le jeton anti-falsification, lu par le navigateur) et <code>SameSite=Lax</code>.</p>
    </article>
</x-layouts.app>
