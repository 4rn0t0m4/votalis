<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AppealService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Contestations tranchées par le comité éditorial (capacité `arbitrate-appeals`). */
class AppealController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $appeals = Appeal::query()
            ->with(['logEntry.target'])
            ->where('status', AppealStatus::Pending)
            ->orderBy('created_at')
            ->get();

        $decided = Appeal::query()->with(['logEntry'])->where('status', '!=', AppealStatus::Pending)->orderByDesc('decided_at')->limit(20)->get();

        return view('moderation.appeals.index', ['appeals' => $appeals, 'decided' => $decided, 'user' => $user]);
    }

    public function show(Appeal $appeal): View
    {
        $appeal->load(['logEntry.target']);
        $target = $appeal->logEntry->target;

        if ($target instanceof Proposal) {
            $target->load(['theme', 'sources']);
        }

        return view('moderation.appeals.show', ['appeal' => $appeal, 'target' => $target]);
    }

    public function decide(Request $request, Appeal $appeal, AppealService $service): RedirectResponse
    {
        /** @var User $arbiter */
        $arbiter = $request->user();

        $data = $request->validate([
            'decision' => ['required', 'in:confirm,overturn'],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ], ['decision.required' => 'Choisissez : confirmer ou annuler la décision.']);

        $service->decide($arbiter, $appeal, $data['decision'] === 'overturn', $data['decision_note'] ?? null);

        return redirect()->route('moderation.appeals.index')->with('status', __('Contestation tranchée. L’issue est inscrite au journal public et l’auteur en est informé.'));
    }
}
