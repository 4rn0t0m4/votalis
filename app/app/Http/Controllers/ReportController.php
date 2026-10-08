<?php

namespace App\Http\Controllers;

use App\Enums\ReportMotive;
use App\Models\User;
use App\Services\ReportService;
use App\Support\ModerationTarget;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Formulaire de signalement d'une proposition ou d'un argument (CDC 4.8). */
class ReportController extends Controller
{
    public function create(string $type, int $id): View
    {
        $target = ModerationTarget::resolve($type, $id);
        Gate::authorize('report', $target);

        return view('reports.create', ['target' => $target, 'type' => $type, 'motives' => ReportMotive::cases()]);
    }

    public function store(Request $request, string $type, int $id, ReportService $service): RedirectResponse
    {
        $target = ModerationTarget::resolve($type, $id);

        $data = $request->validate([
            'motive' => ['required', Rule::enum(ReportMotive::class)],
            'details' => ['nullable', 'string', 'max:300'],
        ], [
            'motive.required' => 'Choisissez un motif dans la liste.',
            'details.max' => 'La précision ne dépasse pas 300 caractères.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $service->report($user, $target, ReportMotive::from($data['motive']), $data['details'] ?? null);

        return redirect()->to($target->url())->with('status', __('Merci. Votre signalement sera examiné par la modération ; la décision figurera dans le journal public.'));
    }
}
