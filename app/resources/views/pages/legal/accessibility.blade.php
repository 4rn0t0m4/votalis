<x-layouts.app title="Accessibilité">
    @php($legal = config('votalis.legal'))
    <article class="max-w-2xl">
        <h1 class="text-3xl font-semibold">Déclaration d'accessibilité</h1>
        <p class="mt-4 text-ink-700">{{ $legal['controller_name'] }} s'engage à rendre cette plateforme accessible, conformément au référentiel général d'amélioration de l'accessibilité (RGAA 4.1), niveau AA. Cette déclaration s'applique à l'ensemble du site.</p>

        <h2 class="mt-8 text-xl font-semibold">État de conformité</h2>
        <p class="mt-3 text-ink-700">La plateforme est en <strong>conformité partielle</strong> avec le RGAA 4.1 : les cinq pages principales (accueil, thème, fiche de proposition, vote rapide, inscription) ont été vérifiées par un audit automatisé (axe-core et HTML CodeSniffer, zéro erreur au 8 octobre 2026) et une auto-évaluation manuelle des 13 thématiques du référentiel, publiée dans le dépôt du code (<code>docs/accessibilite.md</code>). Un audit par un tiers n'a pas encore été réalisé ; cette déclaration sera mise à jour à son issue.</p>

        <h2 class="mt-8 text-xl font-semibold">Ce qui est en place</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Navigation complète au clavier, lien d'évitement vers le contenu, ordre de focus logique, focus visible.</li>
            <li>Structure : un titre principal par page, hiérarchie de titres, régions nommées, fil d'Ariane, tableaux avec en-têtes.</li>
            <li>Formulaires : chaque champ a une étiquette, les champs obligatoires sont annoncés, les erreurs sont reliées aux champs et annoncées.</li>
            <li>Contrastes conformes AA, aucune information portée par la couleur seule, texte agrandissable à 200 %.</li>
            <li>Aucune animation ni média ; pas de limite de temps pour voter ou rédiger.</li>
            <li>Vote rapide utilisable d'une main sur mobile : boutons d'au moins 44 pixels.</li>
        </ul>

        <h2 class="mt-8 text-xl font-semibold">Contenus non accessibles ou à améliorer</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Les mises à jour dynamiques (vote, jauge d'arbitrage, panneau de doublons) sont annoncées par des zones de statut ; leur comportement avec chaque lecteur d'écran n'a pas été vérifié de manière exhaustive.</li>
            <li>Les contenus publiés par les participants (textes des propositions et arguments) ne sont pas relus pour leur accessibilité.</li>
        </ul>

        <h2 class="mt-8 text-xl font-semibold">Nous contacter</h2>
        <p class="mt-3 text-ink-700">Si vous ne parvenez pas à accéder à un contenu ou à un service, écrivez à {{ $legal['contact_email'] }} en précisant la page concernée. Nous répondons sous un mois.</p>

        <h2 class="mt-8 text-xl font-semibold">Voies de recours</h2>
        <p class="mt-3 text-ink-700">Si vous n'obtenez pas de réponse satisfaisante, vous pouvez saisir le <a href="https://formulaire.defenseurdesdroits.fr/" rel="noopener" class="underline">Défenseur des droits</a> ou l'un de ses délégués.</p>

        <p class="mt-8 text-xs text-ink-500">Déclaration établie le 8 octobre 2026.</p>
    </article>
</x-layouts.app>
