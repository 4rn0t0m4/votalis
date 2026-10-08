<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\SignalStatus;
use App\Http\Controllers\Controller;
use App\Models\IntegritySignal;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Signaux d'intégrité : lecture pour modérateurs, comité et administrateur ; statut modifiable par la modération seule. */
class SignalController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-integrity-signals');

        $status = $request->query('statut');
        $status = is_string($status) ? SignalStatus::tryFrom($status) : null;
        $statusValue = $status?->value;

        $signals = IntegritySignal::query()
            ->when($statusValue, fn ($q) => $q->where('status', $statusValue))
            ->orderByRaw("CASE status WHEN 'new' THEN 0 ELSE 1 END")
            ->orderByDesc('severity')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('moderation.signals.index', [
            'signals' => $signals,
            'status' => $status,
            'statuses' => SignalStatus::cases(),
            'canReview' => $request->user()?->can('moderate') ?? false,
        ]);
    }

    public function update(Request $request, IntegritySignal $signal): RedirectResponse
    {
        Gate::authorize('moderate');
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate(['status' => ['required', Rule::enum(SignalStatus::class)]]);

        $signal->forceFill([
            'status' => SignalStatus::from($data['status']),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()->route('moderation.signals.index')->with('status', __('Statut du signal mis à jour. Aucune action n’est appliquée automatiquement.'));
    }
}
