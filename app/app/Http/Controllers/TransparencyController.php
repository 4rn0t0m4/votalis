<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use App\Models\TransparencyReport;
use Illuminate\Contracts\View\View;

/** Rapports de transparence publics (CDC section 6). */
class TransparencyController extends Controller
{
    public function index(): View
    {
        return view('pages.transparency', [
            'reports' => TransparencyReport::query()->orderByDesc('period_start')->get(),
            'motives' => ReportMotive::cases(),
            'actions' => ModerationAction::cases(),
        ]);
    }
}
