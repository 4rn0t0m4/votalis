<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Models\ModerationLogEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Journal public de modération (CDC section 6) : lisible par tous, filtrable, jamais modifiable. */
class ModerationLogController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $action = $request->query('action');

        $type = in_array($type, ['proposal', 'argument'], true) ? $type : null;
        $action = is_string($action) ? ModerationAction::tryFrom($action) : null;
        $actionValue = $action?->value;

        $entries = ModerationLogEntry::query()
            ->when($type, fn ($q) => $q->where('target_type', $type))
            ->when($actionValue, fn ($q) => $q->where('action', $actionValue))
            ->with('target')
            ->orderByDesc('id')
            ->paginate((int) config('votalis.moderation.log_per_page', 50))
            ->withQueryString();

        return view('moderation-log.index', [
            'entries' => $entries,
            'type' => $type,
            'action' => $action,
            'actions' => ModerationAction::cases(),
        ]);
    }

    public function show(ModerationLogEntry $entry): View
    {
        $entry->load('target');

        return view('moderation-log.show', ['entry' => $entry]);
    }
}
