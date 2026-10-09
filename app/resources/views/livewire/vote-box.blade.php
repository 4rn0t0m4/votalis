<section class="card border-[3px] border-accent-600 shadow-glow" aria-labelledby="vote-{{ $proposal->id }}">
    @guest
        <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Votre avis, en deux questions</h2>
        <p class="mt-2 text-ink-700"><a href="{{ route('login') }}" class="link">Connectez-vous</a> pour voter. Les résultats détaillés s'affichent après votre vote, pour ne pas influencer votre réponse.</p>
    @endguest

    @auth
        @if ($isAuthor)
            <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Votre proposition</h2>
            <p class="mt-2 text-ink-700">Vous êtes l'auteur de cette proposition : vous ne votez pas dessus.</p>
            @if ($results)
                @include('proposals._results', ['results' => $results, 'consensus' => $consensus])
            @endif
        @elseif (! $canVote)
            <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Votre avis, en deux questions</h2>
            <p class="mt-2 text-ink-700">Le vote est réservé aux participants dont l'adresse e-mail est vérifiée.</p>
        @elseif ($vote !== null && ! $revising)
            @if ($justVoted)
                <div class="pop flex items-start gap-4 rounded-2xl bg-accent-600 p-5 text-white" role="status">
                    <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-accent-700" aria-hidden="true"><x-icon name="check" class="size-6" stroke="3" /></span>
                    <div>
                        <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Merci. Votre avis est enregistré.</h2>
                        <p class="mt-1 text-accent-100">Vous êtes la {{ number_format($results['total'], 0, ',', ' ') }}{{ $results['total'] === 1 ? 're' : 'e' }} personne à vous prononcer sur cette mesure.</p>
                    </div>
                </div>
            @else
                <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Votre avis</h2>
            @endif
            <p class="mt-4 flex flex-wrap gap-2 text-sm">
                <x-pill tone="lagoon" class="text-sm"><x-icon name="check" class="size-4" /> Souhaitable : {{ $vote->desirable->label() }}</x-pill>
                <x-pill tone="plum" class="text-sm"><x-icon name="scale" class="size-4" /> Nécessaire : {{ $vote->necessary->label() }}</x-pill>
                @if ($vote->isConditional())
                    <x-pill tone="sand" class="text-sm">à condition que {{ $vote->condition }}</x-pill>
                @endif
            </p>
            @include('partials.celebrations', ['milestones' => array_map(fn (string $k) => \App\Enums\Milestone::from($k), $celebrations)])
            @include('proposals._results', ['results' => $results, 'consensus' => $consensus])
            <button type="button" wire:click="revise" class="btn btn-secondary mt-5"><x-icon name="refresh" class="size-4" /> Réviser mon vote{{ $argumentsVisible ? ' après lecture des arguments' : '' }}</button>
        @else
            <h2 id="vote-{{ $proposal->id }}" class="text-2xl font-extrabold tracking-tight">Votre avis, en deux questions</h2>
            <p class="mt-1 text-sm text-ink-700">Les résultats s'affichent après votre vote, pour ne pas vous influencer. Vous pourrez le réviser après lecture des arguments.</p>
            <form wire:submit="vote" class="mt-5 space-y-6" novalidate>
                @error('cap') <x-alert type="error">{{ $message }}</x-alert> @enderror
                @error('proposal') <x-alert type="error">{{ $message }}</x-alert> @enderror

                <div class="grid gap-6 md:grid-cols-2">
                    <fieldset>
                        <legend class="text-lg font-extrabold">Souhaitable pour vous ?</legend>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach ($values as $value)
                                <label class="choice">
                                    <input type="radio" wire:model="desirable" value="{{ $value->value }}" name="desirable-{{ $proposal->id }}">
                                    <x-icon :name="$value->value === 1 ? 'check' : ($value->value === -1 ? 'cross' : 'question')" class="size-6" stroke="2.8" /> {{ $value->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('desirable') <p class="mt-2 text-sm text-red-800">{{ $message }}</p> @enderror
                    </fieldset>

                    <fieldset>
                        <legend class="text-lg font-extrabold">Nécessaire pour le pays, même si elle vous coûte ?</legend>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach ($values as $value)
                                <label class="choice">
                                    <input type="radio" wire:model="necessary" value="{{ $value->value }}" name="necessary-{{ $proposal->id }}">
                                    <x-icon :name="$value->value === 1 ? 'check' : ($value->value === -1 ? 'cross' : 'question')" class="size-6" stroke="2.8" /> {{ $value->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('necessary') <p class="mt-2 text-sm text-red-800">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <div class="border-t border-ink-100 pt-4">
                    <label class="inline-flex min-h-11 items-center gap-2.5 font-semibold">
                        <input type="checkbox" wire:model.live="conditional" class="size-5 rounded border-ink-300 accent-plum-600"> Oui, à condition que…
                    </label>
                    @if ($conditional)
                        <label for="condition-{{ $proposal->id }}" class="sr-only">Condition</label>
                        <input id="condition-{{ $proposal->id }}" type="text" wire:model="condition" maxlength="200" placeholder="…la mesure soit évaluée au bout de trois ans" class="field mt-2">
                        <p class="mt-1 text-xs text-ink-700">200 caractères maximum. Les conditions sont regroupées et affichées sur la fiche.</p>
                    @endif
                    @error('condition') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-button size="lg" class="w-full sm:w-auto">{{ $vote ? 'Enregistrer mon vote révisé' : 'Voter' }} <x-icon name="arrow-right" class="size-5" /></x-button>
                    @if ($vote)
                        <button type="button" wire:click="cancel" class="btn btn-ghost">Annuler</button>
                    @endif
                </div>
            </form>
        @endif
    @endauth
</section>
