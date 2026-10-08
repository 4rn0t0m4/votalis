<x-layouts.app title="Mes données">
    <p class="text-sm"><a href="{{ route('account.show') }}" class="underline">← Mon compte</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Mes données</h1>
    <p class="mt-3 max-w-2xl text-sm text-ink-700">Vous pouvez à tout moment obtenir une copie de vos données ou supprimer votre compte, sans rien demander à personne. Le détail de ce que nous conservons et pourquoi est dans la <a href="{{ route('privacy') }}" class="underline">politique de confidentialité</a>.</p>

    <section class="mt-8 max-w-2xl rounded-lg border border-ink-200 bg-white p-5" aria-labelledby="export">
        <h2 id="export" class="text-lg font-semibold">Exporter mes données</h2>
        <p class="mt-2 text-sm text-ink-700">Un fichier JSON lisible contenant votre compte, vos votes et leurs conditions, vos propositions, arguments, réponses aux arbitrages, signalements, contestations et les décisions de modération vous concernant. Il est généré à la demande et n'est pas conservé.</p>
        <form method="POST" action="{{ route('account.data.export') }}" class="mt-4">
            @csrf
            <x-button variant="secondary">Télécharger mes données (JSON)</x-button>
        </form>
    </section>

    <section class="mt-6 max-w-2xl rounded-lg border border-red-800/30 bg-white p-5" aria-labelledby="suppression">
        <h2 id="suppression" class="text-lg font-semibold">Supprimer mon compte</h2>
        <p class="mt-2 text-sm text-ink-700">Vos votes, réponses aux arbitrages et clés d'accès sont effacés immédiatement. Vos propositions et arguments publiés restent en ligne, rattachés à « participant supprimé », car d'autres participants ont voté et argumenté dessus. Cette action est irréversible.</p>
        <p class="mt-4"><a href="{{ route('account.data.delete') }}" class="inline-flex items-center rounded border border-red-800/40 bg-white px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-50">Supprimer mon compte…</a></p>
    </section>
</x-layouts.app>
