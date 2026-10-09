<?php

namespace App\Http\Controllers\Account;

use App\Enums\Milestone;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Journey;
use Illuminate\Contracts\View\View;

/** « Mon parcours » : page strictement privée du participant connecté. */
class JourneyController extends Controller
{
    public function show(Journey $journey): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('account.journey', [
            'stats' => $journey->stats($user),
            'reached' => $journey->reached($user),
            'milestones' => Milestone::cases(),
            'fresh' => $journey->takeFresh($user),
        ]);
    }
}
