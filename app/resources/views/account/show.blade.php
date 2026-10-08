<x-layouts.app title="Mon compte">
    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Mon compte</h1>
    <dl class="mt-6 flex flex-wrap gap-3">
        <div class="card-sand py-4 sm:py-4"><dt class="eyebrow text-ink-700">Pseudonyme</dt><dd class="mt-1 text-lg font-extrabold">{{ auth()->user()->pseudonym }}</dd></div>
        <div class="card-sand py-4 sm:py-4"><dt class="eyebrow text-ink-700">Rôle</dt><dd class="mt-1 text-lg font-extrabold">{{ auth()->user()->role->label() }}</dd></div>
        <div class="card-sand py-4 sm:py-4"><dt class="eyebrow text-ink-700">Membre depuis</dt><dd class="mt-1 text-lg font-extrabold">{{ auth()->user()->created_at?->translatedFormat('j F Y') }}</dd></div>
    </dl>
    @if (auth()->user()->isSuspended())
        <x-alert type="error" class="mt-6 max-w-2xl">Votre compte est suspendu @if (auth()->user()->suspended_until) jusqu'au {{ auth()->user()->suspended_until->translatedFormat('j F Y') }}@endif : vous pouvez lire et contester, mais plus contribuer. <a href="{{ route('account.moderation.index') }}" class="link">Voir la décision</a>.</x-alert>
    @endif
    <ul class="mt-8 grid gap-4 md:grid-cols-2">
        <li class="card-plum card-lift relative flex items-start gap-4">
            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-plum-600 text-white" aria-hidden="true"><x-icon name="compass" class="size-6" /></span>
            <div>
                <a href="{{ route('account.journey') }}" class="text-lg font-extrabold text-ink-900 no-underline after:absolute after:inset-0 hover:underline">Mon parcours</a>
                <p class="mt-1 text-sm text-ink-700">Vos chiffres et vos jalons, visibles par vous seul·e.</p>
            </div>
        </li>
        <li class="card card-lift relative flex items-start gap-4">
            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-ink-900 text-white" aria-hidden="true"><x-icon name="lock" class="size-6" /></span>
            <div>
                <a href="{{ route('security.show') }}" class="text-lg font-extrabold text-ink-900 no-underline after:absolute after:inset-0 hover:underline">Sécurité du compte</a>
                <p class="mt-1 text-sm text-ink-700">Double authentification et clés d'accès.</p>
            </div>
        </li>
        <li class="card card-lift relative flex items-start gap-4">
            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-ink-900 text-white" aria-hidden="true"><x-icon name="flag" class="size-6" /></span>
            <div>
                <a href="{{ route('account.moderation.index') }}" class="text-lg font-extrabold text-ink-900 no-underline after:absolute after:inset-0 hover:underline">Modération</a>
                <p class="mt-1 text-sm text-ink-700">Décisions concernant mes contributions et contestations.</p>
            </div>
        </li>
        <li class="card card-lift relative flex items-start gap-4">
            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-ink-900 text-white" aria-hidden="true"><x-icon name="shield" class="size-6" /></span>
            <div>
                <a href="{{ route('account.data.show') }}" class="text-lg font-extrabold text-ink-900 no-underline after:absolute after:inset-0 hover:underline">Mes données</a>
                <p class="mt-1 text-sm text-ink-700">Export et suppression du compte.</p>
            </div>
        </li>
    </ul>
</x-layouts.app>
