<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use App\Services\ModerationQueue;
use App\Services\ModerationService;
use App\Support\ModerationTarget;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Un dossier de modération : le contenu, son contexte, les signalements et les actions. */
class CaseController extends Controller
{
    public function show(string $type, int $id, ModerationQueue $queue): View
    {
        $target = ModerationTarget::resolve($type, $id);
        $target->load($target instanceof Proposal ? ['theme', 'author', 'sources'] : ['proposal', 'author']);

        $reports = $target->reports()->orderByDesc('created_at')->get();

        return view('moderation.case', [
            'target' => $target,
            'type' => $type,
            'openReports' => $reports->where('status', ReportStatus::Open),
            'pastReports' => $reports->where('status', ReportStatus::Handled),
            'reporterHistories' => $reports->mapWithKeys(fn (Report $r) => [$r->id => $queue->reporterHistory($r)]),
            'author' => $queue->authorContext($target),
            'entries' => $target->moderationEntries()->get(),
            'motives' => ReportMotive::cases(),
        ]);
    }

    public function keep(string $type, int $id, Request $request, ModerationService $service): RedirectResponse
    {
        $target = ModerationTarget::resolve($type, $id);
        /** @var User $moderator */
        $moderator = $request->user();

        $service->keep($moderator, $target);

        return redirect()->route('moderation.queue')->with('status', __('Contenu conservé. La décision est inscrite au journal public.'));
    }

    public function hide(string $type, int $id, Request $request, ModerationService $service): RedirectResponse
    {
        $target = ModerationTarget::resolve($type, $id);
        /** @var User $moderator */
        $moderator = $request->user();

        $data = $request->validate([
            'motive' => ['required', Rule::enum(ReportMotive::class)],
            'kept_proposal_id' => ['nullable', 'integer'],
        ], ['motive.required' => 'Choisissez le motif du masquage.']);

        $service->hide($moderator, $target, ReportMotive::from($data['motive']), isset($data['kept_proposal_id']) ? (int) $data['kept_proposal_id'] : null);

        return redirect()->route('moderation.queue')->with('status', __('Contenu masqué. La décision est inscrite au journal public.'));
    }

    /** Suspension du compte de l'auteur, par le comité éditorial seulement. */
    public function suspend(string $type, int $id, Request $request, ModerationService $service): RedirectResponse
    {
        $target = ModerationTarget::resolve($type, $id);
        /** @var User $editorial */
        $editorial = $request->user();

        $data = $request->validate([
            'motive' => ['required', Rule::enum(ReportMotive::class)],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], ['motive.required' => 'Choisissez le motif de la suspension.']);

        $author = $target->author;

        if ($author === null) {
            abort(404);
        }

        $service->suspend($editorial, $author, ReportMotive::from($data['motive']), isset($data['days']) ? (int) $data['days'] : null);

        return redirect()->route('moderation.case', ['type' => $type, 'id' => $id])->with('status', __('Compte suspendu. La décision est inscrite au journal public et le titulaire en est informé.'));
    }

    public function requestRewrite(string $type, int $id, Request $request, ModerationService $service): RedirectResponse
    {
        $target = ModerationTarget::resolve($type, $id);
        /** @var User $moderator */
        $moderator = $request->user();

        if (! $target instanceof Proposal) {
            abort(404);
        }

        $data = $request->validate([
            'motive' => ['required', Rule::enum(ReportMotive::class)],
        ], ['motive.required' => 'Choisissez le motif de la demande.']);

        $service->requestRewrite($moderator, $target, ReportMotive::from($data['motive']));

        return redirect()->route('moderation.queue')->with('status', __('Reformulation demandée à l’auteur. La décision est inscrite au journal public.'));
    }
}
