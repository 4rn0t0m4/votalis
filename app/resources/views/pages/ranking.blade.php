<x-layouts.app title="Comment fonctionne le classement">
    @php($repo = rtrim((string) config('votalis.repository_url'), '/'))
    <article class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Comment fonctionne le classement</h1>
        <p class="mt-4 text-ink-700">Il n'existe pas de classement unique. Aucune page ne trie les propositions par nombre de soutiens, parce qu'un tel tri récompense la mobilisation plutôt que la qualité d'une idée. Chaque thème propose plusieurs lectures, décrites ici en langage simple. Les formules exactes sont dans le code, que chacun peut lire.</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Ce que nous mesurons</h2>
        <p class="mt-3 text-ink-700">Pour chaque proposition, chaque participant répond à deux questions : est-elle <strong>souhaitable</strong> pour lui, et <strong>nécessaire</strong> pour le pays même si elle lui coûte. Les réponses sont « oui », « non » ou « je ne sais pas ». Nous conservons aussi le premier vote de chacun et son vote révisé après lecture des arguments.</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Les onglets</h2>
        <dl class="mt-3 divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white">
            <div class="px-4 py-3"><dt class="font-medium">Récentes</dt><dd class="mt-1 text-sm text-ink-700">Les dernières fiches publiées, sans aucun calcul.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus débattues</dt><dd class="mt-1 text-sm text-ink-700">On additionne les votes et les arguments publiés. Une fiche très commentée monte, qu'elle soit approuvée ou non.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus clivantes</dt><dd class="mt-1 text-sm text-ink-700">On regarde si les « oui » et les « non » à la question « souhaitable » s'équilibrent. Plus l'écart est faible, plus la fiche est clivante. Il faut au moins {{ config('votalis.rankings.min_votes') }} votes pour éviter qu'un ou deux avis ne décident. Quand le classement par consensus est actif, on mesure plutôt l’écart d’accord entre le groupe de votants le plus favorable et le moins favorable.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Nécessaires mais pas souhaitées</dt><dd class="mt-1 text-sm text-ink-700">On compare la part de « oui » à « nécessaire » et la part de « oui » à « souhaitable ». Les fiches que l'on juge utiles au pays sans les vouloir pour soi apparaissent ici, à partir de {{ config('votalis.rankings.min_votes') }} votes.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Soutien en hausse après les arguments</dt><dd class="mt-1 text-sm text-ink-700">Parmi les participants qui ont révisé leur vote après avoir lu les arguments, quelle part est passée de « non » ou « je ne sais pas » à « oui » ? Cet onglet mesure l'effet de l'information, pas la popularité.</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus choisies dans les arbitrages</dt><dd class="mt-1 text-sm text-ink-700">Quand les participants composent une combinaison de mesures sous contrainte, quelle part retient celle-ci ? Choisir une mesure en renonçant à d'autres pèse plus qu'un simple « oui ».</dd></div>
            <div class="px-4 py-3"><dt class="font-medium">Les plus consensuelles</dt><dd class="mt-1 text-sm text-ink-700">Les fiches approuvées à la fois par des votants qui, par ailleurs, ne votent pas de la même façon. Détail <a href="#consensus" class="link">ci-dessous</a>.</dd></div>
        </dl>

        @inject('consensusService', 'App\Services\Consensus')
        @php($consensusRun = $consensusService->activeRun())
        <h2 id="consensus" class="mt-8 text-2xl font-extrabold tracking-tight">Le classement par consensus</h2>
        <p class="mt-3 text-ink-700">L'idée, inspirée de Pol.is : une mesure n'est pas consensuelle parce que beaucoup de gens la soutiennent, mais parce qu'elle est approuvée par des personnes qui, sur le reste, ne pensent pas pareil.</p>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-ink-700">
            <li>On ne retient que les participants ayant voté au moins {{ config('votalis.consensus.min_votes_per_participant') }} fois.</li>
            <li>On les regroupe automatiquement selon la ressemblance de leurs réponses à « souhaitable », en deux à cinq groupes. Les groupes ne sont ni nommés ni décrits, et l'appartenance de chacun n'est jamais enregistrée.</li>
            <li>Pour chaque fiche et chaque groupe, on calcule un taux d'accord prudent, qui ne tire pas de conclusion d'un petit nombre de votes. « Je ne sais pas » compte parmi les votants mais pas comme un accord.</li>
            <li>Le score d'une fiche est le taux d'accord du groupe le <strong>moins</strong> favorable. Il faut donc convaincre tous les groupes, pas seulement le plus nombreux.</li>
            <li>Un groupe qui compte trop peu de votants sur une fiche est écarté pour cette fiche, sans pouvoir la bloquer : un groupe ne peut pas empêcher le classement d'une mesure en s'abstenant. Il faut au moins deux groupes représentés.</li>
        </ol>
        <p class="mt-3 text-ink-700">Le calcul est refait toutes les heures et donne toujours le même résultat pour les mêmes votes. Il ne démarre qu'au-delà de {{ config('votalis.consensus.min_participants') }} participants et {{ config('votalis.consensus.min_proposals') }} propositions publiées : en dessous, les groupes ne seraient pas significatifs.</p>
        <p class="mt-3 rounded-2xl bg-mist-100 p-4 text-sm text-ink-900">
            @if ($consensusRun)
                Dernier calcul : {{ $consensusRun->computed_at->translatedFormat('j F Y à H\hi') }}, {{ trans_choice(':count participant retenu|:count participants retenus', $consensusRun->participants) }}, {{ $consensusRun->k }} groupes, version {{ $consensusRun->algo_version }}.
            @else
                {{ $consensusService->unavailableReason() }}
            @endif
        </p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Ce qui n'entre jamais dans le calcul</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>L'identité, l'ancienneté ou le rôle des votants : un vote vaut un vote.</li>
            <li>Les propositions masquées par la modération : elles sortent de tous les onglets, de la recherche et du vote rapide.</li>
            <li>Toute pondération éditoriale : le comité ne peut ni promouvoir ni rétrograder une fiche.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Vote rapide</h2>
        <p class="mt-3 text-ink-700">Le vote rapide présente une fiche à la fois, tirée au sort avec un avantage aux fiches récentes (moins de {{ config('votalis.quick_vote.recent_days') }} jours) et aux fiches peu votées (moins de {{ config('votalis.quick_vote.low_votes_threshold') }} votes), pour que chaque proposition ait sa chance d'être lue.</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Lire le code</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li><a href="{{ $repo }}/blob/main/app/app/Services/Rankings.php" rel="noopener" class="link">Le service de classement</a>, qui calcule chaque onglet.</li>
            <li><a href="{{ $repo }}/blob/main/docs/classement.md" rel="noopener" class="link">Les définitions formelles</a>, mises à jour à chaque changement.</li>
            <li><a href="{{ $repo }}/blob/main/app/app/Services/QuickVoteSelector.php" rel="noopener" class="link">Le tirage du vote rapide</a>.</li>
            <li><a href="{{ $repo }}/blob/main/consensus/app/consensus.py" rel="noopener" class="link">Le calcul par consensus</a>, et le script qui permet de le refaire à partir d'un export pseudonymisé.</li>
        </ul>
    </article>
</x-layouts.app>
