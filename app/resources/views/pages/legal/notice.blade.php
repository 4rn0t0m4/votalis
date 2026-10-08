<x-layouts.app title="Mentions légales">
    @php($legal = config('votalis.legal'))
    <article class="max-w-2xl">
        <h1 class="text-3xl font-semibold">Mentions légales</h1>

        <h2 class="mt-8 text-xl font-semibold">Éditeur</h2>
        <p class="mt-3 text-ink-700">{{ $legal['controller_name'] }}, {{ $legal['controller_address'] }}. Contact : {{ $legal['contact_email'] }}. Directeur de la publication : {{ $legal['publication_director'] }}.</p>

        <h2 class="mt-8 text-xl font-semibold">Hébergeur</h2>
        <p class="mt-3 text-ink-700">{{ $legal['host_name'] }}, {{ $legal['host_address'] }}. Hébergement en Union européenne.</p>

        <h2 class="mt-8 text-xl font-semibold">Nature du service</h2>
        <p class="mt-3 text-ink-700">Plateforme indépendante et non partisane de débat citoyen. Elle n'est liée à aucun parti, aucune campagne ni aucune institution, et ne reçoit aucune instruction éditoriale extérieure. Les contenus publiés par les participants n'engagent que leurs auteurs ; ils sont modérés selon la <a href="{{ route('charter') }}" class="underline">charte de modération</a>, et chaque décision figure au <a href="{{ route('moderation-log.index') }}" class="underline">journal public</a>.</p>

        <h2 class="mt-8 text-xl font-semibold">Code source et licence</h2>
        <p class="mt-3 text-ink-700">Le logiciel est publié sous licence <a href="https://www.gnu.org/licenses/agpl-3.0.fr.html" rel="noopener" class="underline">GNU AGPL v3</a> ; son code est consultable sur <a href="{{ config('votalis.repository_url') }}" rel="noopener" class="underline">le dépôt public</a>. Les contributions des participants (propositions, arguments) sont publiées sous licence <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.fr" rel="noopener" class="underline">Creative Commons BY-SA 4.0</a> afin de pouvoir être réutilisées dans le débat public.</p>

        <h2 class="mt-8 text-xl font-semibold">Signaler un contenu ou une faille</h2>
        <p class="mt-3 text-ink-700">Un contenu : bouton « Signaler » sur chaque proposition et argument, pour les participants connectés, ou {{ $legal['contact_email'] }}. Une faille de sécurité : voir le fichier <code>SECURITY.md</code> du dépôt.</p>

        <p class="mt-8 text-sm text-ink-700">Voir aussi la <a href="{{ route('privacy') }}" class="underline">politique de confidentialité</a> et la <a href="{{ route('cookies') }}" class="underline">politique cookies</a>.</p>
    </article>
</x-layouts.app>
