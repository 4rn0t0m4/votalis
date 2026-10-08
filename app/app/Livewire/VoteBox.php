<?php

namespace App\Livewire;

use App\Enums\VoteValue;
use App\Models\Proposal;
use App\Models\User;
use App\Services\VoteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Bloc de vote d'une fiche : deux questions, condition, résultats après le vote.
 * Sur la fiche, les arguments sont visibles : une révision compte « après lecture des arguments ».
 */
class VoteBox extends Component
{
    #[Locked]
    public int $proposalId;

    /** Les arguments sont-ils visibles dans la vue qui héberge le bloc ? */
    #[Locked]
    public bool $argumentsVisible = true;

    public ?int $desirable = null;

    public ?int $necessary = null;

    public bool $conditional = false;

    public string $condition = '';

    public bool $revising = false;

    public function mount(Proposal $proposal, bool $argumentsVisible = true): void
    {
        $this->proposalId = $proposal->id;
        $this->argumentsVisible = $argumentsVisible;
    }

    public function revise(): void
    {
        $vote = app(VoteService::class)->voteOf(auth()->user() instanceof User ? auth()->user() : null, $this->proposal());

        if ($vote !== null) {
            $this->desirable = $vote->desirable->value;
            $this->necessary = $vote->necessary->value;
            $this->conditional = $vote->isConditional();
            $this->condition = (string) $vote->condition;
        }

        $this->revising = true;
    }

    public function cancel(): void
    {
        $this->revising = false;
    }

    public function vote(VoteService $service): void
    {
        $proposal = $this->proposal();
        Gate::authorize('vote', $proposal);

        $this->validate([
            'desirable' => ['required', 'integer', 'in:-1,0,1'],
            'necessary' => ['required', 'integer', 'in:-1,0,1'],
            'condition' => ['nullable', 'string', 'max:'.VoteService::CONDITION_MAX],
        ], [
            'desirable.required' => 'Répondez à la question « Souhaitable pour vous ? ».',
            'necessary.required' => 'Répondez à la question « Nécessaire pour le pays ? ».',
            'condition.max' => 'La condition ne doit pas dépasser :max caractères.',
        ]);

        /** @var User $user */
        $user = auth()->user();

        $service->cast(
            $user,
            $proposal,
            VoteValue::from((int) $this->desirable),
            VoteValue::from((int) $this->necessary),
            $this->conditional ? $this->condition : null,
            $this->argumentsVisible,
        );

        $this->revising = false;
        $this->dispatch('voted', proposalId: $proposal->id);
    }

    public function render(VoteService $service): View
    {
        $proposal = $this->proposal();
        $user = auth()->user() instanceof User ? auth()->user() : null;
        $vote = $service->voteOf($user, $proposal);

        return view('livewire.vote-box', [
            'proposal' => $proposal,
            'vote' => $vote,
            'canVote' => $user !== null && Gate::forUser($user)->allows('vote', $proposal),
            'isAuthor' => $user !== null && $proposal->author_id === $user->id,
            'results' => $vote !== null ? $service->results($proposal) : null,
            'values' => VoteValue::cases(),
        ]);
    }

    private function proposal(): Proposal
    {
        return Proposal::query()->findOrFail($this->proposalId);
    }
}
