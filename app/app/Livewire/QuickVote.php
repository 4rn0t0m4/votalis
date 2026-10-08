<?php

namespace App\Livewire;

use App\Models\Proposal;
use App\Models\User;
use App\Services\QuickVoteSelector;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Vote rapide : une proposition à la fois, arguments repliés, passage à la suivante. */
class QuickVote extends Component
{
    public ?int $proposalId = null;

    /** @var list<int> */
    public array $skipped = [];

    public bool $argumentsShown = false;

    public bool $justVoted = false;

    public function mount(QuickVoteSelector $selector): void
    {
        $this->pick($selector);
    }

    public function skip(QuickVoteSelector $selector): void
    {
        if ($this->proposalId !== null) {
            $this->skipped[] = $this->proposalId;
        }

        $this->pick($selector);
    }

    public function next(QuickVoteSelector $selector): void
    {
        $this->pick($selector);
    }

    public function showArguments(): void
    {
        $this->argumentsShown = true;
    }

    #[On('voted')]
    public function onVoted(): void
    {
        $this->justVoted = true;
    }

    public function render(): View
    {
        $proposal = $this->proposalId !== null
            ? Proposal::query()->with(['theme.parent', 'sources', 'author'])->find($this->proposalId)
            : null;

        return view('livewire.quick-vote', ['proposal' => $proposal]);
    }

    private function pick(QuickVoteSelector $selector): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->proposalId = $selector->next($user, $this->skipped)?->id;
        $this->argumentsShown = false;
        $this->justVoted = false;
    }
}
