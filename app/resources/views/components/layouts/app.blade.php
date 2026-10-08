@props(['title' => null])
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-ink-50 text-ink-900 antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-white focus:px-3 focus:py-2 focus:rounded">
        Aller au contenu
    </a>

    <header class="border-b border-ink-200 bg-white">
        <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3" aria-label="Navigation principale">
            <a href="{{ route('home') }}" class="font-semibold text-ink-900">{{ config('app.name') }}</a>
            <ul class="flex items-center gap-4 text-sm">
                <li><a href="{{ route('themes.index') }}" class="hover:underline">Thèmes</a></li>
                <li><a href="{{ route('search') }}" class="hover:underline">Rechercher</a></li>
                <li><a href="{{ route('how-it-works') }}" class="hover:underline">Comment ça marche</a></li>
                @can('manage-themes')
                    <li><a href="{{ route('committee.themes.index') }}" class="hover:underline">Comité</a></li>
                @endcan
                @auth
                    <li><a href="{{ route('quick-vote') }}" class="hover:underline">Vote rapide</a></li>
                    <li><a href="{{ route('account.show') }}" class="hover:underline">Mon compte</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="hover:underline">Se déconnecter</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:underline">Se connecter</a></li>
                    <li><a href="{{ route('register') }}" class="rounded bg-accent-600 px-3 py-1.5 font-medium text-white hover:bg-accent-700">Créer un compte</a></li>
                @endauth
            </ul>
        </nav>
    </header>

    <main id="contenu" class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
        @if (session('status'))
            <x-alert type="info" class="mb-6">{{ session('status') }}</x-alert>
        @endif
        {{ $slot }}
    </main>

    <footer class="border-t border-ink-200 bg-white text-sm text-ink-500">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-4">
            <p>Plateforme indépendante et non partisane. Code source libre (AGPL v3).</p>
            <p>
                <a href="{{ config('votalis.repository_url') }}" rel="noopener" class="hover:underline">Dépôt du code</a>
                @if (config('votalis.commit'))
                    · version <code>{{ Str::limit(config('votalis.commit'), 8, '') }}</code>
                @endif
            </p>
        </div>
    </footer>
</body>
</html>
