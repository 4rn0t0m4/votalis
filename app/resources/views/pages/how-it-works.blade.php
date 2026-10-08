<x-layouts.app title="Comment ça marche">
    <article class="prose max-w-2xl">
        <h1 class="text-3xl font-semibold">Comment ça marche</h1>
        <p class="mt-4 text-ink-700">Cette plateforme recense des mesures concrètes, chacune présentée avec son problème, sa mesure, son coût estimé et ses sources. Vous les lisez, vous les votez, vous les débattez, et vous pourrez bientôt arbitrer entre elles sous contrainte.</p>
        <h2 class="mt-8 text-xl font-semibold">Deux questions par proposition</h2>
        <p class="mt-3 text-ink-700">Pour chaque mesure, vous répondez à deux questions : est-elle <strong>souhaitable pour vous</strong>, et est-elle <strong>nécessaire pour le pays, même si elle vous coûte</strong> ? Vous pouvez répondre « oui, à condition que… ». Votre vote est modifiable ; nous conservons votre premier vote et votre vote révisé après lecture des arguments, pour mesurer l'effet de l'information. Les résultats détaillés ne s'affichent qu'après votre vote.</p>

        <h2 class="mt-8 text-xl font-semibold">Pas de classement unique</h2>
        <p class="mt-3 text-ink-700">Aucune liste ne trie les propositions par nombre de soutiens. Chaque thème propose plusieurs onglets :</p>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li><strong>Les plus débattues</strong> : le plus de votes et d'arguments réunis.</li>
            <li><strong>Les plus clivantes</strong> : les « oui » et les « non » s'équilibrent, à partir de {{ config('votalis.rankings.min_votes') }} votes.</li>
            <li><strong>Nécessaires mais pas souhaitées</strong> : plus de participants les jugent nécessaires que souhaitables.</li>
            <li><strong>Soutien en hausse après les arguments</strong> : la part des votes passés à « oui » après lecture des arguments.</li>
            <li><strong>Les plus choisies dans les arbitrages</strong> : la part des participants qui retiennent la mesure quand ils composent une combinaison sous contrainte.</li>
            <li><strong>Les plus consensuelles</strong> : en V2, un classement par familles de votants, inspiré de Pol.is, dont l'algorithme sera publié ici avec un lien vers le code.</li>
        </ul>
        <p class="mt-3 text-ink-700">Les définitions exactes sont publiées dans le dépôt du code (<code>docs/classement.md</code>).</p>

        <h2 class="mt-8 text-xl font-semibold">Ce qui est déjà garanti</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Un compte par adresse e-mail vérifiée ; les adresses jetables sont refusées.</li>
            <li>Votre e-mail est chiffré et n'est jamais affiché : seul votre pseudonyme est public.</li>
            <li>Aucune affiliation politique n'est demandée ni affichée.</li>
            <li>Le code source est public sous licence AGPL v3.</li>
        </ul>
    </article>
</x-layouts.app>
