<?php

namespace App\Livewire;

use App\Enums\ThemeStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\DuplicateFinder;
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

    /** @var array<int, array{id: int, title: string, theme: string, url: string, similarity: int}> */
    public array $similar = [];

    public bool $similarChecked = false;

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

    public function updatedTitle(): void
    {
        $this->refreshSimilar();
    }

    public function updatedMeasure(): void
    {
        $this->refreshSimilar();
    }

    /** Détection de doublons (CDC 4.5) : déclenchée dès que titre et mesure sont assez renseignés. */
    public function refreshSimilar(): void
    {
        $finder = app(DuplicateFinder::class);

        if (! $finder->enoughText($this->title, $this->measure)) {
            $this->similar = [];
            $this->similarChecked = false;

            return;
        }

        $exclude = $this->proposal !== null ? [$this->proposal->id] : [];

        $this->similar = $finder->similarTo($this->title, $this->measure, $exclude)
            ->map(fn (Proposal $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'theme' => $p->theme->fullName(),
                'url' => $p->url(),
                'similarity' => (int) round(100 * (float) $p->getAttribute('similarity')),
            ])
            ->values()
            ->all();
        $this->similarChecked = true;
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
            $rewrite = $this->proposal->awaitsRewrite();
            $proposal = $service->update($this->proposal, $input, $user);
            session()->flash('status', $rewrite ? 'Votre reformulation est enregistrée et la proposition est de nouveau visible.' : 'Votre correction a été enregistrée.');
        } else {
            Gate::authorize('create', Proposal::class);
            $proposal = $service->create($input, $user);
            session()->flash('status', 'Votre proposition est publiée. Merci de votre contribution.');
        }

        $this->redirect($proposal->url());
    }

    public function render(): View
    {
        $proposal = $this->proposal;
        $locked = $proposal !== null && $proposal->isLocked() && ! $proposal->awaitsRewrite();

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
            'rewrite' => $this->proposal?->awaitsRewrite() ?? false,
            'rewriteMotive' => $this->proposal?->hidden_motive?->label(),
            'limits' => [
                'title' => ProposalRules::TITLE_MAX,
                'problem' => ProposalRules::PROBLEM_MAX,
                'measure' => ProposalRules::MEASURE_MAX,
                'cost_estimate' => ProposalRules::COST_MAX,
            ],
        ]);
    }
}
