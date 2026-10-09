<x-layouts.app title="Transparence">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Rapports de transparence</h1>
        <p class="mt-4 text-ink-700">Chaque trimestre, la plateforme publie des chiffres agrégés sur la modération : signalements par motif, décisions, contestations et leur issue, comptes suspendus, opérations coordonnées confirmées. Aucun chiffre ne permet d'identifier une personne. Le détail de chaque décision est dans le <a href="{{ route('moderation-log.index') }}" class="link">journal public</a>.</p>

        @forelse ($reports as $report)
            @php($d = $report->data)
            <section class="mt-8 card" aria-labelledby="rapport-{{ $report->id }}">
                <h2 id="rapport-{{ $report->id }}" class="text-xl font-semibold">{{ $report->title() }}</h2>
                <p class="mt-1 text-xs text-ink-700">Généré le {{ $report->generated_at->translatedFormat('j F Y') }}</p>

                <div class="mt-4 grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="eyebrow text-ink-700">Signalements ({{ $d['reports']['total'] ?? 0 }})</h3>
                        <table class="mt-2 w-full text-sm">
                            <tbody class="divide-y divide-ink-200">
                                @foreach ($motives as $motive)
                                    <tr><td class="py-1">{{ $motive->label() }}</td><td class="py-1 text-right">{{ $d['reports']['by_motive'][$motive->value] ?? 0 }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h3 class="eyebrow text-ink-700">Décisions ({{ $d['decisions']['total'] ?? 0 }})</h3>
                        <table class="mt-2 w-full text-sm">
                            <tbody class="divide-y divide-ink-200">
                                @foreach ($actions as $action)
                                    <tr><td class="py-1">{{ $action->label() }}</td><td class="py-1 text-right">{{ $d['decisions']['by_action'][$action->value] ?? 0 }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-500">Contestations déposées</dt><dd class="font-medium">{{ $d['appeals']['filed'] ?? 0 }}</dd></div>
                    <div><dt class="text-ink-500">Contestations acceptées / rejetées</dt><dd class="font-medium">{{ $d['appeals']['overturned'] ?? 0 }} / {{ $d['appeals']['confirmed'] ?? 0 }}</dd></div>
                    <div><dt class="text-ink-500">Comptes suspendus</dt><dd class="font-medium">{{ $d['suspensions'] ?? 0 }}</dd></div>
                    <div><dt class="text-ink-500">Opérations coordonnées confirmées</dt><dd class="font-medium">{{ $d['coordinated_operations'] ?? 0 }} (sur {{ $d['signals_raised'] ?? 0 }} signaux levés)</dd></div>
                </dl>
            </section>
        @empty
            <p class="mt-8 text-sm text-ink-700">Aucun rapport publié pour l'instant. Le premier paraîtra à la fin du trimestre en cours.</p>
        @endforelse
    </article>
</x-layouts.app>
