<x-layouts.app title="Charte de modération">
    <article class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Charte de modération</h1>
        <p class="mt-4 text-ink-700">La modération protège le débat, pas des opinions. Elle juge des contenus, jamais des personnes, et n'applique aucune autre règle que celles écrites ici. Toute décision est motivée, inscrite au <a href="{{ route('moderation-log.index') }}" class="link">journal public</a> et contestable.</p>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Les sept motifs</h2>
        <p class="mt-3 text-ink-700">Un contenu ne peut être signalé ni masqué que pour l'un de ces motifs. La liste est fermée.</p>
        <dl class="mt-3 divide-y divide-ink-200 rounded-3xl border-2 border-ink-200 bg-white">
            @foreach (\App\Enums\ReportMotive::cases() as $motive)
                <div class="px-4 py-3">
                    <dt class="font-medium">{{ $motive->label() }}</dt>
                    <dd class="mt-1 text-sm text-ink-700">{{ $motive->description() }}</dd>
                </div>
            @endforeach
        </dl>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Signaler</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Tout participant vérifié peut signaler une proposition ou un argument, une fois par contenu, dans la limite de {{ config('votalis.caps.reports_per_day') }} signalements par jour.</li>
            <li>Le contenu signalé reste visible jusqu'à la décision. Un contenu signalé comme illégal est masqué immédiatement, en attendant la décision ; s'il est conservé, il redevient visible.</li>
            <li>L'identité du signaleur n'est transmise ni à l'auteur ni aux modérateurs, qui ne voient que l'historique agrégé de ses signalements.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Décider</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>Les signalements sont traités par gravité puis par ancienneté.</li>
            <li>Trois décisions sont possibles : <strong>conserver</strong>, <strong>masquer</strong> avec motif, <strong>demander une reformulation</strong> à l'auteur. Rien n'est supprimé.</li>
            <li>Une fiche masquée comme doublon renvoie vers la fiche conservée.</li>
            <li>Un argument masqué est remplacé par un bandeau indiquant le motif.</li>
            <li>Les modérateurs agissent sous un second facteur d'authentification ; le journal n'affiche jamais leur identité, seulement leur rôle.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Contester</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-ink-700">
            <li>L'auteur d'un contenu peut contester une décision une fois, dans les {{ config('votalis.moderation.appeal_days') }} jours.</li>
            <li>Le comité éditorial tranche. Un membre ne traite jamais la contestation de sa propre décision.</li>
            <li>Une décision annulée rétablit le contenu ; l'issue est inscrite au journal.</li>
        </ul>

        <h2 class="mt-8 text-2xl font-extrabold tracking-tight">Rendre compte</h2>
        <p class="mt-3 text-ink-700">Un rapport de transparence est publié chaque trimestre : volumes par motif, décisions, contestations et leur issue, comptes suspendus, opérations coordonnées détectées. Le code de la plateforme est public sous licence AGPL v3.</p>
    </article>
</x-layouts.app>
