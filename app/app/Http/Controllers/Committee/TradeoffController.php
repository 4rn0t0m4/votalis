<?php

namespace App\Http\Controllers\Committee;

use App\Enums\SuggestionStatus;
use App\Enums\TradeoffDirection;
use App\Enums\TradeoffStatus;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Models\TradeoffItem;
use App\Models\TradeoffSuggestion;
use App\Services\TradeoffService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Administration des arbitrages par le comité éditorial (CDC 4.9). */
class TradeoffController extends Controller
{
    public function __construct(private readonly TradeoffService $service) {}

    public function index(): View
    {
        Gate::authorize('manage', Tradeoff::class);

        return view('committee.tradeoffs.index', [
            'tradeoffs' => Tradeoff::query()->withCount(['items', 'answers', 'suggestions' => fn ($q) => $q->where('status', SuggestionStatus::Pending)])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Tradeoff::class);

        return view('committee.tradeoffs.form', ['tradeoff' => new Tradeoff, 'themes' => $this->themes()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Tradeoff::class);

        $data = $this->validated($request);

        $tradeoff = Tradeoff::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug((string) $data['title']),
            'objective' => $data['objective'],
            'constraint_value' => $data['constraint_value'],
            'unit' => $data['unit'],
            'direction' => $data['direction'],
            'source_url' => $data['source_url'],
            'source_label' => $data['source_label'],
            'theme_id' => $data['theme_id'],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Arbitrage créé en brouillon. Ajoutez au moins deux mesures chiffrées avant de l’ouvrir.');
    }

    public function edit(Tradeoff $tradeoff): View
    {
        Gate::authorize('update', $tradeoff);

        $tradeoff->load(['items.proposal', 'suggestions.proposal', 'suggestions.participant']);

        $candidates = Proposal::query()->published()
            ->when($tradeoff->theme_id, fn ($q) => $q->whereHas('theme', fn ($t) => $t->where('id', $tradeoff->theme_id)->orWhere('parent_id', $tradeoff->theme_id)))
            ->whereNotIn('id', $tradeoff->items->pluck('proposal_id'))
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('committee.tradeoffs.form', ['tradeoff' => $tradeoff, 'themes' => $this->themes(), 'candidates' => $candidates]);
    }

    public function update(Request $request, Tradeoff $tradeoff): RedirectResponse
    {
        Gate::authorize('update', $tradeoff);

        $data = $this->validated($request);
        $tradeoff->update([
            'title' => $data['title'],
            'objective' => $data['objective'],
            'constraint_value' => $data['constraint_value'],
            'unit' => $data['unit'],
            'direction' => $data['direction'],
            'source_url' => $data['source_url'],
            'source_label' => $data['source_label'],
            'theme_id' => $data['theme_id'],
        ]);

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Arbitrage mis à jour.');
    }

    public function status(Request $request, Tradeoff $tradeoff): RedirectResponse
    {
        Gate::authorize('update', $tradeoff);

        $status = TradeoffStatus::from((string) $request->validate(['status' => ['required', Rule::enum(TradeoffStatus::class)]])['status']);
        $this->service->changeStatus($tradeoff, $status);

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Statut : '.$status->label().'.');
    }

    public function storeItem(Request $request, Tradeoff $tradeoff): RedirectResponse
    {
        Gate::authorize('update', $tradeoff);

        $data = $request->validate([
            'proposal_id' => ['required', 'integer'],
            'impact' => ['required', 'numeric'],
            'uncertainty' => ['required', 'string', 'max:120'],
            'source_url' => ['required', 'string', 'url:http,https', 'max:500'],
        ], [
            'impact.required' => 'Indiquez l’impact estimé.',
            'uncertainty.required' => 'Indiquez l’incertitude du chiffrage.',
            'source_url.required' => 'Le chiffrage doit être sourcé.',
            'source_url.url' => 'La source doit être une adresse web complète.',
        ]);

        $this->service->addItem($tradeoff, [
            'proposal_id' => (int) $data['proposal_id'],
            'impact' => (float) $data['impact'],
            'uncertainty' => (string) $data['uncertainty'],
            'source_url' => (string) $data['source_url'],
        ]);

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Mesure ajoutée.');
    }

    public function destroyItem(Tradeoff $tradeoff, TradeoffItem $item): RedirectResponse
    {
        Gate::authorize('update', $tradeoff);
        abort_unless($item->tradeoff_id === $tradeoff->id, 404);

        if ($tradeoff->answers()->exists()) {
            return redirect()->route('committee.tradeoffs.edit', $tradeoff)->withErrors(['items' => 'Des participants ont déjà répondu : les mesures ne se retirent plus. Clôturez l’arbitrage et créez-en un nouveau.']);
        }

        $item->delete();

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Mesure retirée.');
    }

    public function rejectSuggestion(Tradeoff $tradeoff, TradeoffSuggestion $suggestion): RedirectResponse
    {
        Gate::authorize('update', $tradeoff);
        abort_unless($suggestion->tradeoff_id === $tradeoff->id, 404);

        $suggestion->update(['status' => SuggestionStatus::Rejected]);

        return redirect()->route('committee.tradeoffs.edit', $tradeoff)->with('status', 'Suggestion écartée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'objective' => ['required', 'string', 'min:10', 'max:1000'],
            'constraint_value' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'direction' => ['required', Rule::enum(TradeoffDirection::class)],
            'source_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'source_label' => ['nullable', 'string', 'max:200'],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
        ], [], [
            'title' => 'titre', 'objective' => 'objectif', 'constraint_value' => 'contrainte', 'unit' => 'unité',
            'direction' => 'sens de la contrainte', 'source_url' => 'source', 'source_label' => 'libellé de la source', 'theme_id' => 'thème',
        ]);

        return [
            'title' => $data['title'],
            'objective' => $data['objective'],
            'constraint_value' => round((float) $data['constraint_value'], 2),
            'unit' => $data['unit'],
            'direction' => $data['direction'],
            'source_url' => $data['source_url'] ?? null,
            'source_label' => $data['source_label'] ?? null,
            'theme_id' => $data['theme_id'] ?? null,
        ];
    }

    /**
     * @return Collection<int, Theme>
     */
    private function themes(): Collection
    {
        return Theme::query()->listed()->roots()->orderBy('position')->orderBy('name')->get();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'arbitrage';
        $slug = $base;
        $i = 2;

        while (Tradeoff::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
