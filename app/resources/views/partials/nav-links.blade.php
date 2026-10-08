{{-- Liens de navigation. Mode « desktop » : les outils des rôles sont regroupés dans un menu déroulant ; mode « mobile » : liste à plat. --}}
@php
    $mode = $mode ?? 'mobile';
    $link = fn (string $route, string $label, ?string $pattern = null) => sprintf(
        '<li><a href="%s" class="nav-link whitespace-nowrap"%s>%s</a></li>',
        route($route),
        request()->routeIs($pattern ?? $route.'*') ? ' aria-current="page"' : '',
        e($label),
    );
    $tools = [];
    if (auth()->user()?->can('moderate')) {
        $tools[] = ['moderation.queue', 'Modération', 'moderation.queue'];
    }
    if (auth()->user()?->can('arbitrate-appeals')) {
        $tools[] = ['moderation.appeals.index', 'Contestations', 'moderation.appeals.*'];
    }
    if (auth()->user()?->can('view-integrity-signals')) {
        $tools[] = ['moderation.signals.index', 'Signaux', 'moderation.signals.*'];
    }
    if (auth()->user()?->can('manage-platform')) {
        $tools[] = ['admin.index', 'Administration', 'admin.*'];
    }
    if (auth()->user()?->can('manage-themes')) {
        $tools[] = ['committee.themes.index', 'Thèmes (comité)', 'committee.themes.*'];
        $tools[] = ['committee.tradeoffs.index', 'Arbitrages (comité)', 'committee.tradeoffs.*'];
    }
@endphp
{!! $link('themes.index', 'Thèmes', 'themes.*') !!}
{!! $link('tradeoffs.index', 'Arbitrages', 'tradeoffs.*') !!}
{!! $link('search', 'Rechercher') !!}
{!! $link('how-it-works', 'Comment ça marche') !!}
@if ($tools !== [] && $mode === 'desktop')
    <li>
        <details class="group relative">
            <summary class="nav-link cursor-pointer list-none whitespace-nowrap [&::-webkit-details-marker]:hidden">Outils <x-icon name="chevron-down" class="size-4 transition-transform group-open:rotate-180" /></summary>
            <ul class="absolute left-0 z-20 mt-2 flex w-64 flex-col gap-1 rounded-3xl border-2 border-ink-200 bg-white p-2 shadow-lift">
                @foreach ($tools as [$route, $label, $pattern])
                    {!! $link($route, $label, $pattern) !!}
                @endforeach
            </ul>
        </details>
    </li>
@else
    @foreach ($tools as [$route, $label, $pattern])
        {!! $link($route, $label, $pattern) !!}
    @endforeach
@endif
@auth
    {!! $link('account.show', 'Mon compte', 'account.*') !!}
    <li>
        <a href="{{ route('quick-vote') }}" class="btn btn-primary w-full whitespace-nowrap xl:w-auto" @if (request()->routeIs('quick-vote')) aria-current="page" @endif>
            <x-icon name="bolt" class="size-4" /> Vote rapide
        </a>
    </li>
    <li>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link whitespace-nowrap text-ink-700">Se déconnecter</button>
        </form>
    </li>
@else
    {!! $link('login', 'Se connecter') !!}
    <li><a href="{{ route('register') }}" class="btn btn-primary w-full whitespace-nowrap xl:w-auto">Créer un compte</a></li>
@endauth
