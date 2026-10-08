<?php

namespace App\Livewire;

use App\Enums\ThemeStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\ProposalRules;
use App\Services\ProposalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Formulaire de fiche de proposition (création et correction) : compteurs de caractères,
 * sources dynamiques, validation finale côté serveur via ProposalService.
 */
class ProposalForm extends Component
{
    public ?Proposal $proposal = null;

    public ?int $theme_id = null;

    public string $title = '';

    public string $problem = '';

    public string $measure = '';

    public string $cost_estimate = '';

    public bool $cost_unknown = false;

    /** @var array<int, string> */
    public array $sources = [''];

    public bool $personal_source = false;

    public function mount(?Proposal $proposal = null, ?int $theme = null): void
    {
        if ($proposal !== null && $proposal->exists) {
            Gate::authorize('update', $proposal);

            $this->proposal = $proposal;
            $this->theme_id = $proposal->theme_id;
            $this->title = $proposal->title;
            $this->problem = $proposal->problem;
            $this->measure = $proposal->measure;
            $this->cost_estimate = (string) $proposal->cost_estimate;
            $this->cost_unknown = $proposal->cost_unknown;
            $urls = $proposal->sources->where('is_personal', false)->pluck('url')->filter()->map(fn ($u) => (string) $u)->values()->all();
            $this->sources = $urls === [] ? [''] : $urls;
            $this->personal_source = $proposal->sources->contains('is_personal', true);
        } else {
            Gate::authorize('create', Proposal::class);
            $this->theme_id = $theme;
        }
    }

    public function addSource(): void
    {
        if (count($this->sources) < 10) {
            $this->sources[] = '';
        }
    }

    public function removeSource(int $index): void
    {
        unset($this->sources[$index]);
        $values = array_values($this->sources);
        $this->sources = $values === [] ? [''] : $values;
    }

    public function save(ProposalService $service): void
    {
        /** @var User $user */
        $user = auth()->user();

        $input = [
            'theme_id' => $this->theme_id,
            'title' => $this->title,
            'problem' => $this->problem,
            'measure' => $this->measure,
            'cost_estimate' => $this->cost_estimate,
            'cost_unknown' => $this->cost_unknown,
            'sources' => $this->sources,
            'personal_source' => $this->personal_source,
        ];

        if ($this->proposal !== null) {
            Gate::authorize('update', $this->proposal);
            $proposal = $service->update($this->proposal, $input, $user);
            session()->flash('status', 'Votre correction a été enregistrée.');
        } else {
            Gate::authorize('create', Proposal::class);
            $proposal = $service->create($input, $user);
            session()->flash('status', 'Votre proposition est publiée. Merci de votre contribution.');
        }

        $this->redirect($proposal->url());
    }

    public function render(): View
    {
        $locked = $this->proposal?->isLocked() ?? false;

        $themes = Theme::query()
            ->where('status', ThemeStatus::Open)
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Theme $t) => $t->fullName());

        return view('livewire.proposal-form', [
            'themes' => $themes,
            'locked' => $locked,
            'limits' => [
                'title' => ProposalRules::TITLE_MAX,
                'problem' => ProposalRules::PROBLEM_MAX,
                'measure' => ProposalRules::MEASURE_MAX,
                'cost_estimate' => ProposalRules::COST_MAX,
            ],
        ]);
    }
}
