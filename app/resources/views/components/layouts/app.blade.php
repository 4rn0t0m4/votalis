@props(['title' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#faf8f5">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-ink-50 text-ink-900 antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:font-bold">
        Aller au contenu
    </a>

    <header class="border-b border-ink-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3" aria-label="Navigation principale">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight whitespace-nowrap text-ink-900 no-underline">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-600 text-white" aria-hidden="true"><x-icon name="layers" class="size-5" /></span>
                {{ config('app.name') }}
            </a>

            <ul class="hidden items-center gap-1 xl:flex">
                @include('partials.nav-links', ['mode' => 'desktop'])
            </ul>

            <details class="relative xl:hidden">
                <summary class="btn btn-secondary list-none [&::-webkit-details-marker]:hidden">
                    <x-icon name="menu" class="size-5" /> Menu
                </summary>
                <ul class="absolute right-0 z-20 mt-2 flex w-72 flex-col gap-1 rounded-3xl border-2 border-ink-200 bg-white p-2 shadow-lift">
                    @include('partials.nav-links', ['mode' => 'mobile'])
                </ul>
            </details>
        </nav>
    </header>

    <main id="contenu" class="mx-auto w-full flex-1 px-4 py-8 sm:py-10 {{ $wide ? 'max-w-7xl' : 'max-w-6xl' }}">
        @if (app(\App\Services\ReadOnlyMode::class)->enabled())
            <x-alert type="info" class="mb-6">{{ app(\App\Services\ReadOnlyMode::class)->message() }}</x-alert>
        @endif
        @if (session('status'))
            <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
        @endif
        @auth
            @include('partials.celebrations', ['milestones' => app(\App\Services\Journey::class)->takeFresh(auth()->user())])
        @endauth
        {{ $slot }}
    </main>

    <footer class="border-t border-ink-200 bg-white text-sm text-ink-700">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-3">
            <div>
                <p class="flex items-center gap-2 font-extrabold text-ink-900"><span class="inline-flex size-6 items-center justify-center rounded-lg bg-accent-600 text-white" aria-hidden="true"><x-icon name="layers" class="size-3.5" /></span>{{ config('app.name') }}</p>
                <p class="mt-2">Plateforme indépendante et non partisane. Code source libre (AGPL v3).</p>
                <p class="mt-2">
                    <a href="{{ config('votalis.repository_url') }}" rel="noopener" class="link">Dépôt du code</a>
                    @if (config('votalis.commit'))
                        · version <code>{{ Str::limit(config('votalis.commit'), 8, '') }}</code>
                    @endif
                </p>
            </div>
            <ul class="space-y-1.5">
                <li><a href="{{ route('charter') }}" class="hover:underline">Charte de modération</a></li>
                <li><a href="{{ route('moderation-log.index') }}" class="hover:underline">Journal de modération</a></li>
                <li><a href="{{ route('ranking-explained') }}" class="hover:underline">Comment fonctionne le classement</a></li>
                <li><a href="{{ route('transparency') }}" class="hover:underline">Transparence</a></li>
            </ul>
            <ul class="space-y-1.5">
                <li><a href="{{ route('privacy') }}" class="hover:underline">Confidentialité</a></li>
                <li><a href="{{ route('legal-notice') }}" class="hover:underline">Mentions légales</a></li>
                <li><a href="{{ route('cookies') }}" class="hover:underline">Cookies</a></li>
                <li><a href="{{ route('accessibility') }}" class="hover:underline">Accessibilité</a></li>
            </ul>
        </div>
    </footer>
</body>
</html>
