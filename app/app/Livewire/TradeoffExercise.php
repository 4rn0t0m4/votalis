<?php

namespace App\Livewire;

use App\Models\Proposal;
use App\Models\Tradeoff;
use App\Models\User;
use App\Services\Journey;
use App\Services\TradeoffService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Exercice d'arbitrage (CDC 4.9) : le participant compose sa combinaison, la jauge se met à jour,
 * la validation n'est possible que si la contrainte est atteinte (vérifiée aussi côté serveur).
 */
class TradeoffExercise extends Component
{
    #[Locked]
    public int $tradeoffId;

    /** @var list<int> */
    public array $selected = [];

    /** @var array<int, string> */
    public array $conditions = [];

    public ?int $suggestionProposalId = null;

    public string $suggestionNote = '';

    public bool $editing = false;

    /** @var list<string> Jalons atteints par la validation, célébrés une fois. */
    public array $celebrations = [];

    public function mount(Tradeoff $tradeoff): void
    {
        $this->tradeoffId = $tradeoff->id;

        $answer = app(TradeoffService::class)->answerOf($this->user(), $tradeoff);

        if ($answer !== null) {
            $this->selected = $answer->item_ids;
            $this->conditions = array_combine(array_map('intval', array_keys($answer->conditions)), array_values($answer->conditions));
        } else {
            $this->editing = true;
        }
    }

    public function toggle(int $itemId): void
    {
        if (in_array($itemId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$itemId]));
            unset($this->conditions[$itemId]);
        } else {
            $this->selected[] = $itemId;
        }
    }

    public function edit(): void
    {
        $this->editing = true;
    }

    public function submit(TradeoffService $service): void
    {
        $tradeoff = $this->tradeoff();
        Gate::authorize('answer', $tradeoff);

        /** @var User $user */
        $user = $this->user();

        $service->answer($user, $tradeoff, $this->selected, $this->conditions);

        $this->editing = false;
        $this->celebrations = array_map(fn ($m) => $m->value, app(Journey::class)->takeFresh($user));
        session()->flash('tradeoff-status', 'Votre combinaison est enregistrée. Seule la dernière compte ; votre historique reste visible ci-dessous.');
    }

    public function suggest(TradeoffService $service): void
    {
        $tradeoff = $this->tradeoff();
        Gate::authorize('answer', $tradeoff);

        /** @var User $user */
        $user = $this->user();

        $this->validate(['suggestionProposalId' => ['required', 'integer']], ['suggestionProposalId.required' => 'Choisissez une proposition.']);

        $service->suggest($user, $tradeoff, (int) $this->suggestionProposalId, $this->suggestionNote);

        $this->reset('suggestionProposalId', 'suggestionNote');
        session()->flash('tradeoff-status', 'Merci : le comité éditorial examinera le chiffrage de cette mesure avant de l’ajouter.');
    }

    public function render(TradeoffService $service): View
    {
        $tradeoff = $this->tradeoff()->load(['items.proposal.theme']);
        $user = $this->user();

        $total = round($tradeoff->items->whereIn('id', $this->selected)->sum(fn ($item) => $item->impactValue()), 2);
        $target = $tradeoff->target();
        $progress = $target > 0 ? (int) min(100, round(100 * $total / $target)) : 100;

        $answer = $service->answerOf($user, $tradeoff);

        $candidates = $user !== null
            ? Proposal::query()->published()
                ->when($tradeoff->theme_id, fn ($q) => $q->whereHas('theme', fn ($t) => $t->where('id', $tradeoff->theme_id)->orWhere('parent_id', $tradeoff->theme_id)))
                ->whereNotIn('id', $tradeoff->items->pluck('proposal_id'))
                ->orderBy('title')->limit(300)->get(['id', 'title'])
            : collect();

        return view('livewire.tradeoff-exercise', [
            'tradeoff' => $tradeoff,
            'total' => $total,
            'target' => $target,
            'progress' => $progress,
            'satisfied' => $tradeoff->satisfiedBy($total),
            'canAnswer' => $user !== null && Gate::forUser($user)->allows('answer', $tradeoff),
            'answer' => $answer,
            'history' => $user !== null ? $service->historyOf($user, $tradeoff) : collect(),
            'candidates' => $candidates,
        ]);
    }

    private function tradeoff(): Tradeoff
    {
        return Tradeoff::query()->findOrFail($this->tradeoffId);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
