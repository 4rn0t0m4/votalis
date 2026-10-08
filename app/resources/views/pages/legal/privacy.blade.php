<x-layouts.app title="Politique de confidentialité">
    @php($legal = config('votalis.legal'))
    <article class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Politique de confidentialité</h1>
        <p class="mt-4 text-ink-700">Cette plateforme traite peu de données, et seulement pour faire fonctionner le débat. Vos votes et contributions peuvent révéler des opinions politiques : ce sont des données sensibles, traitées uniquement avec votre consentement explicite, donné à l'inscription et retirable à tout moment en supprimant votre compte.</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Responsable de traitement</h2>
        <p class="mt-3 text-ink-700">{{ $legal['controller_name'] }}, {{ $legal['controller_address'] }}. Contact : {{ $legal['contact_email'] }}.@if ($legal['dpo_email']) Délégué à la protection des données : {{ $legal['dpo_email'] }}.@endif</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Données traitées et finalités</h2>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-ink-700"><tr><th class="py-2">Donnée</th><th class="py-2">Finalité</th><th class="py-2">Base légale</th><th class="py-2">Conservation</th></tr></thead>
            <tbody class="divide-y divide-ink-200">
                <tr><td class="py-2">Pseudonyme</td><td class="py-2">Signer vos contributions publiques</td><td class="py-2">Contrat (fonctionnement du service)</td><td class="py-2">Durée du compte</td></tr>
                <tr><td class="py-2">E-mail (chiffré, avec un haché séparé)</td><td class="py-2">Un compte par personne, connexion, avis de modération et de conservation</td><td class="py-2">Contrat</td><td class="py-2">Durée du compte</td></tr>
                <tr><td class="py-2">Mot de passe (haché Argon2id), second facteur, clés d'accès</td><td class="py-2">Sécurité du compte</td><td class="py-2">Contrat, intérêt légitime (sécurité)</td><td class="py-2">Durée du compte</td></tr>
                <tr><td class="py-2">Votes, conditions, réponses aux arbitrages</td><td class="py-2">Produire les résultats et classements, liés à un identifiant interne, jamais à l'e-mail</td><td class="py-2">Consentement explicite (art. 9 RGPD)</td><td class="py-2">Durée du compte ; effacés à la suppression</td></tr>
                <tr><td class="py-2">Propositions, arguments</td><td class="py-2">Alimenter le débat public</td><td class="py-2">Consentement explicite</td><td class="py-2">Conservés après suppression, rattachés à « participant supprimé »</td></tr>
                <tr><td class="py-2">Signalements, contestations</td><td class="py-2">Modération et transparence</td><td class="py-2">Intérêt légitime (intégrité du débat)</td><td class="py-2">Conservés sans auteur ni texte libre après suppression</td></tr>
                <tr><td class="py-2">Date de dernière visite (au jour près)</td><td class="py-2">Supprimer les comptes inactifs</td><td class="py-2">Intérêt légitime (minimisation)</td><td class="py-2">Durée du compte</td></tr>
            </tbody>
        </table>
        <p class="mt-3 text-sm text-ink-700">Nous ne demandons ni date de naissance, ni adresse, ni profession, ni orientation politique. Nous n'enregistrons pas d'adresse IP ni de journal de connexion. Aucun cookie de suivi ni mesure d'audience tierce (<a href="{{ route('cookies') }}" class="link">politique cookies</a>).</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Qui voit quoi</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Public : pseudonyme, propositions, arguments, résultats agrégés des votes, journal de modération (sans identité des modérateurs ni des signaleurs).</li>
            <li>Modérateurs et comité éditorial : contenus signalés, pseudonyme de l'auteur, ancienneté du compte, décisions antérieures. Jamais votre e-mail ni vos votes individuels.</li>
            <li>Administrateur technique : exploitation du service, sans pouvoir éditorial.</li>
            <li>Sous-traitants : hébergeur et fournisseur d'envoi d'e-mails, tous deux établis en Union européenne et non soumis au Cloud Act. Aucun transfert hors de l'Union européenne.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Conservation</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Compte inactif depuis {{ config('votalis.retention.inactive_months') }} mois : vous êtes prévenu par e-mail {{ config('votalis.retention.notice_days') }} jours avant, puis le compte est supprimé comme si vous l'aviez demandé.</li>
            <li>Sessions et jetons de réinitialisation : purgés à expiration.</li>
            <li>Sauvegardes chiffrées : conservées au plus 30 jours.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Vos droits</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li><strong>Accès et portabilité</strong> : téléchargez vos données en JSON depuis <a href="{{ route('account.data.show') }}" class="link">Mes données</a>.</li>
            <li><strong>Rectification</strong> : pseudonyme et e-mail depuis votre compte ; propositions corrigeables tant qu'elles n'ont pas reçu de vote, puis seulement sur la forme.</li>
            <li><strong>Effacement et retrait du consentement</strong> : supprimez votre compte en libre-service ; vos votes sont effacés immédiatement.</li>
            <li><strong>Opposition, limitation</strong> et toute autre demande : {{ $legal['contact_email'] }}. Réponse sous un mois.</li>
            <li><strong>Réclamation</strong> : auprès de la CNIL, <a href="https://www.cnil.fr/fr/plaintes" rel="noopener" class="link">cnil.fr</a>.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Sécurité</h2>
        <p class="mt-3 text-ink-700">E-mail chiffré en base, mots de passe hachés et vérifiés contre les fuites connues, double authentification proposée à tous et obligatoire pour les rôles privilégiés, en-têtes de sécurité stricts, aucune ressource chargée depuis un tiers, journaux techniques sans donnée personnelle. Le code est public sous licence AGPL v3 et peut être audité par quiconque.</p>

        <p class="mt-8 text-xs text-ink-700">Dernière mise à jour : 8 octobre 2026. Toute modification substantielle est annoncée sur la plateforme avant d'entrer en vigueur.</p>
    </article>
</x-layouts.app>
