<section class="rounded-lg border border-accent-500/40 bg-white p-5" aria-labelledby="vote-{{ $proposal->id }}">
    <h2 id="vote-{{ $proposal->id }}" class="text-lg font-semibold">Votre avis</h2>

    @guest
        <p class="mt-2 text-sm text-ink-700"><a href="{{ route('login') }}" class="underline">Connectez-vous</a> pour voter. Les résultats détaillés s'affichent après votre vote, pour ne pas influencer votre réponse.</p>
    @endguest

    @auth
        @if ($isAuthor)
            <p class="mt-2 text-sm text-ink-700">Vous êtes l'auteur de cette proposition : vous ne votez pas dessus.</p>
            @if ($results)
                @include('proposals._results', ['results' => $results])
            @endif
        @elseif (! $canVote)
            <p class="mt-2 text-sm text-ink-700">Le vote est réservé aux participants dont l'adresse e-mail est vérifiée.</p>
        @elseif ($vote !== null && ! $revising)
            <p class="mt-2 text-sm text-ink-700">
                Votre vote : <strong>{{ $vote->desirable->label() }}</strong> pour « souhaitable »,
                <strong>{{ $vote->necessary->label() }}</strong> pour « nécessaire »@if ($vote->isConditional()), à condition que {{ $vote->condition }}@endif.
            </p>
            @include('proposals._results', ['results' => $results])
            <button type="button" wire:click="revise" class="mt-3 text-sm underline">Réviser mon vote{{ $argumentsVisible ? ' après lecture des arguments' : '' }}</button>
        @else
            <form wire:submit="vote" class="mt-3 space-y-5" novalidate>
                @error('cap') <x-alert type="error">{{ $message }}</x-alert> @enderror
                @error('proposal') <x-alert type="error">{{ $message }}</x-alert> @enderror

                <fieldset>
                    <legend class="text-sm font-medium">Souhaitable pour vous ?</legend>
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($values as $value)
                            <label class="inline-flex items-center gap-1.5 text-sm">
                                <input type="radio" wire:model="desirable" value="{{ $value->value }}" name="desirable-{{ $proposal->id }}" class="h-4 w-4"> {{ $value->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('desirable') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium">Nécessaire pour le pays, même si elle vous coûte ?</legend>
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($values as $value)
                            <label class="inline-flex items-center gap-1.5 text-sm">
                                <input type="radio" wire:model="necessary" value="{{ $value->value }}" name="necessary-{{ $proposal->id }}" class="h-4 w-4"> {{ $value->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('necessary') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label class="inline-flex items-center gap-1.5 text-sm">
                        <input type="checkbox" wire:model.live="conditional" class="h-4 w-4 rounded border-ink-300"> Oui, à condition que…
                    </label>
                    @if ($conditional)
                        <label for="condition-{{ $proposal->id }}" class="sr-only">Condition</label>
                        <input id="condition-{{ $proposal->id }}" type="text" wire:model="condition" maxlength="200" placeholder="…la mesure soit évaluée au bout de trois ans"
                            class="mt-2 block w-full rounded border border-ink-300 px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-ink-500">200 caractères maximum. Les conditions sont regroupées et affichées sur la fiche.</p>
                    @endif
                    @error('condition') <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button>{{ $vote ? 'Enregistrer mon vote révisé' : 'Voter' }}</x-button>
                    @if ($vote)
                        <button type="button" wire:click="cancel" class="text-sm underline">Annuler</button>
                    @endif
                </div>
            </form>
        @endif
    @endauth
</section>
