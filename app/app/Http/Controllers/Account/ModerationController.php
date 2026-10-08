<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AppealService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Mon compte : décisions de modération concernant mes contributions et mon compte, contestation. */
class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $entries = ModerationLogEntry::query()
            ->where(function ($q) use ($user): void {
                $q->where(fn ($q) => $q->where('target_type', 'proposal')->whereIn('target_id', Proposal::query()->where('author_id', $user->id)->select('id')))
                    ->orWhere(fn ($q) => $q->where('target_type', 'argument')->whereIn('target_id', Argument::query()->where('author_id', $user->id)->select('id')))
                    ->orWhere(fn ($q) => $q->where('target_type', 'user')->where('target_id', $user->id));
            })
            ->with(['target', 'appeal'])
            ->orderByDesc('id')
            ->paginate(30);

        return view('account.moderation.index', ['entries' => $entries, 'user' => $user]);
    }

    public function create(ModerationLogEntry $entry): View
    {
        Gate::authorize('appeal', $entry);
        $entry->load('target');

        return view('account.moderation.appeal', ['entry' => $entry]);
    }

    public function store(Request $request, ModerationLogEntry $entry, AppealService $service): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate(['body' => ['required', 'string', 'min:20', 'max:1000']], [
            'body.required' => 'Expliquez votre contestation.',
            'body.min' => 'Expliquez votre contestation en au moins :min caractères.',
            'body.max' => 'La contestation ne dépasse pas :max caractères.',
        ]);

        $service->file($user, $entry, $data['body']);

        return redirect()->route('account.moderation.index')->with('status', __('Votre contestation est transmise au comité éditorial. Vous serez informé de sa décision.'));
    }
}
