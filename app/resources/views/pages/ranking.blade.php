<x-layouts.app title="Comment fonctionne le classement">
    @php($repo = rtrim((string) config('votalis.repository_url'), '/'))
    <article class="max-w-2xl">
        <h1 class="text-3xl font-semibold">Comment fonctionne le classement</h1>
        <p class="mt-4 text-ink-700">Il n'existe pas de classement unique. Aucune page ne trie les propositions par nombre de soutiens, parce qu'un tel tri récompense la mobilisation plutôt que la qualité d'une idée. Chaque thème propose plusieurs lectures, décrites ici en langage simple. Les formules exactes sont dans le code, que chacun peut lire.</p>

        <h2 class="mt-8 text-xl font-semibold">Ce que nous mesurons</h2>
        <p class="mt-3 text-ink-700">Pour chaque proposition, chaque participant répond à deux questions : est-elle <strong>souhaitable</strong> pour lui, et <strong>nécessaire</strong> pour le pays même si elle lui coûte. Les réponses sont « oui », « non » ou « je ne sais pas ». Nous conservons aussi le premier vote de chacun et son vote révisé après lecture des arguments.</p>

        <h2 class="mt-8 text-xl font-semibold">Les onglets</h2>
        <dl class="mt-3 divide-y divide-ink-200 rounded-lg border border-ink-200 bg-white">
            <div class="px-4 py-3"><dt class="font-medium">Récentes</dt><dd class="mt-1 text-sm text-ink-700">Les dernières fiches publiées, sans aucun calcul.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus débattues</dt><dd class="mt-1 text-sm text-ink-700">On additionne les votes et les arguments publiés. Une fiche très commentée monte, qu'elle soit approuvée ou non.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus clivantes</dt><dd class="mt-1 text-sm text-ink-700">On regarde si les « oui » et les « non » à la question « souhaitable » s'équilibrent. Plus l'écart est faible, plus la fiche est clivante. Il faut au moins {{ config('votalis.rankings.min_votes') }} votes pour éviter qu'un ou deux avis ne décident.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Nécessaires mais pas souhaitées</dt><dd class="mt-1 text-sm text-ink-700">On compare la part de « oui » à « nécessaire » et la part de « oui » à « souhaitable ». Les fiches que l'on juge utiles au pays sans les vouloir pour soi apparaissent ici, à partir de {{ config('votalis.rankings.min_votes') }} votes.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Soutien en hausse après les arguments</dt><dd class="mt-1 text-sm text-ink-700">Parmi les participants qui ont révisé leur vote après avoir lu les arguments, quelle part est passée de « non » ou « je ne sais pas » à « oui » ? Cet onglet mesure l'effet de l'information, pas la popularité.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus choisies dans les arbitrages</dt><dd class="mt-1 text-sm text-ink-700">Quand les participants composent une combinaison de mesures sous contrainte, quelle part retient celle-ci ? Choisir une mesure en renonçant à d'autres pèse plus qu'un simple « oui ».</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus consensuelles</dt><dd class="mt-1 text-sm text-ink-700">À venir : un classement par familles de votants, inspiré de Pol.is, qui fait ressortir les mesures approuvées par des groupes qui d'ordinaire ne sont pas d'accord. L'algorithme sera publié ici avant sa mise en service.</dd></div>
        </dl>

        <h2 class="mt-8 text-xl font-semibold">Ce qui n'entre jamais dans le calcul</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>L'identité, l'ancienneté ou le rôle des votants : un vote vaut un vote.</li>
            <li>Les propositions masquées par la modération : elles sortent de tous les onglets, de la recherche et du vote rapide.</li>
            <li>Toute pondération éditoriale : le comité ne peut ni promouvoir ni rétrograder une fiche.</li>
        </ul>

        <h2 class="mt-8 text-xl font-semibold">Vote rapide</h2>
        <p class="mt-3 text-ink-700">Le vote rapide présente une fiche à la fois, tirée au sort avec un avantage aux fiches récentes (moins de {{ config('votalis.quick_vote.recent_days') }} jours) et aux fiches peu votées (moins de {{ config('votalis.quick_vote.low_votes_threshold') }} votes), pour que chaque proposition ait sa chance d'être lue.</p>

        <h2 class="mt-8 text-xl font-semibold">Lire le code</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li><a href="{{ $repo }}/blob/main/app/app/Services/Rankings.php" rel="noopener" class="underline">Le service de classement</a>, qui calcule chaque onglet.</li>
            <li><a href="{{ $repo }}/blob/main/docs/classement.md" rel="noopener" class="underline">Les définitions formelles</a>, mises à jour à chaque changement.</li>
            <li><a href="{{ $repo }}/blob/main/app/app/Services/QuickVoteSelector.php" rel="noopener" class="underline">Le tirage du vote rapide</a>.</li>
        </ul>
    </article>
</x-layouts.app>
