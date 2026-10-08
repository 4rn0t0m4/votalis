<x-layouts.app>
    <section class="grid items-center gap-8 lg:grid-cols-12" aria-labelledby="accroche">
        <div class="rise lg:col-span-7">
            <x-pill tone="plum" class="text-sm">Indépendante, non partisane, code libre</x-pill>
            <h1 id="accroche" class="mt-5 text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Des mesures concrètes. À débattre, à voter, à arbitrer <span class="text-accent-600">ensemble</span>.</h1>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-700 sm:text-xl">
                Chaque proposition est présentée avec son problème, son coût, ses sources et ses arguments pour et contre.
                Vous dites ce que vous souhaitez, ce que vous jugez nécessaire, et vous choisissez sous contrainte.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                @auth
                    <x-button :href="route('quick-vote')" size="lg"><x-icon name="bolt" /> Donner mon avis en 2 minutes</x-button>
                @else
                    <x-button :href="route('register')" size="lg"><x-icon name="check" /> Donner mon avis en 2 minutes</x-button>
                @endauth
                <x-button :href="route('themes.index')" variant="secondary" size="lg">Parcourir les thèmes</x-button>
            </div>
            <p class="mt-4 text-sm text-ink-700">Aucune étiquette politique, pseudonymat par défaut, modération publique. <a href="{{ route('how-it-works') }}" class="link">Comment ça marche</a></p>
        </div>
        <div class="rise rise-2 flex justify-center lg:col-span-5">
            <x-illustration name="hero" />
        </div>
    </section>

    <section class="mt-14" aria-labelledby="pouls">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 id="pouls" class="text-2xl font-extrabold tracking-tight sm:text-3xl">Le pouls de la plateforme</h2>
            <p class="text-sm text-ink-700">Chiffres publics, aucun classement par popularité.</p>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat :value="number_format($pulse['proposals'], 0, ',', ' ')" label="propositions publiées" class="rise" />
            <x-stat :value="number_format($pulse['votes'], 0, ',', ' ')" label="votes exprimés" tone="plum" class="rise rise-2" />
            <x-stat :value="number_format($pulse['answers'], 0, ',', ' ')" label="arbitrages composés" class="rise rise-3" />
            <x-stat :value="number_format($pulse['participants'], 0, ',', ' ')" label="participants" tone="plum" class="rise rise-4" />
        </div>
    </section>

    @auth
        @include('partials.onboarding')
    @endauth

    <section class="mt-14" aria-labelledby="portes">
        <h2 id="portes" class="text-2xl font-extrabold tracking-tight sm:text-3xl">Par où entrer ?</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <a href="{{ auth()->check() ? route('quick-vote') : route('register') }}" class="card-lagoon card-lift flex min-h-56 flex-col gap-4 text-ink-900 no-underline">
                <span class="inline-flex size-13 items-center justify-center rounded-2xl bg-accent-600 text-white" aria-hidden="true"><x-icon name="bolt" class="size-6" /></span>
                <span class="text-2xl leading-tight font-extrabold tracking-tight">Donner mon avis</span>
                <span class="text-ink-700">Une fiche à la fois, deux questions, les arguments à portée de pouce.</span>
            </a>
            <a href="{{ route('themes.index') }}" class="card-sand card-lift flex min-h-56 flex-col gap-4 text-ink-900 no-underline">
                <span class="inline-flex size-13 items-center justify-center rounded-2xl bg-ink-900 text-white" aria-hidden="true"><x-icon name="grid" class="size-6" /></span>
                <span class="text-2xl leading-tight font-extrabold tracking-tight">Explorer un thème</span>
                <span class="text-ink-700">Économie, santé, éducation, défense… plusieurs lectures par thème, jamais un palmarès.</span>
            </a>
            <a href="{{ route('tradeoffs.index') }}" class="card-plum card-lift flex min-h-56 flex-col gap-4 text-ink-900 no-underline">
                <span class="inline-flex size-13 items-center justify-center rounded-2xl bg-plum-600 text-white" aria-hidden="true"><x-icon name="scale" class="size-6" /></span>
                <span class="text-2xl leading-tight font-extrabold tracking-tight">Arbitrer sous contrainte</span>
                <span class="text-ink-700">Composez une combinaison qui tient dans l'enveloppe. Renoncer, c'est choisir.</span>
            </a>
        </div>
    </section>

    @if ($tradeoff)
        <section class="card-ink mt-14 grid items-center gap-8 p-8 sm:p-10 lg:grid-cols-12" aria-labelledby="arbitrage-ouvert">
            <div class="lg:col-span-7">
                <p class="eyebrow text-accent-200">Arbitrage ouvert</p>
                <h2 id="arbitrage-ouvert" class="mt-3 text-3xl leading-tight font-extrabold tracking-tight">{{ $tradeoff->title }}</h2>
                <p class="mt-3 text-lg text-ink-200">{{ $tradeoff->objective }}</p>
                <x-button :href="route('tradeoffs.show', $tradeoff)" class="mt-5 bg-white text-ink-900 shadow-none hover:bg-ink-100">Composer ma combinaison <x-icon name="arrow-right" class="size-4" /></x-button>
            </div>
            <div class="rounded-3xl bg-white/10 p-6 lg:col-span-5">
                <p class="text-3xl font-extrabold tracking-tight">{{ $tradeoff->formatAmount($tradeoff->target()) }}</p>
                <p class="mt-1 text-sm text-ink-200">{{ mb_strtolower($tradeoff->direction->label()) }}, {{ trans_choice(':count mesure chiffrée|:count mesures chiffrées', $tradeoff->items_count) }}</p>
                <p class="mt-4 flex items-center gap-2 text-sm text-ink-200"><x-icon name="user" class="size-4" /> {{ trans_choice(':count participant a déjà validé une combinaison|:count participants ont déjà validé une combinaison', $tradeoff->answers_count) }}</p>
            </div>
        </section>
    @endif
</x-layouts.app>
