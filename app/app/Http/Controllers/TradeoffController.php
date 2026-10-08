<?php

namespace App\Http\Controllers;

use App\Enums\TradeoffStatus;
use App\Models\Tradeoff;
use App\Services\TradeoffService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TradeoffController extends Controller
{
    public function index(): View
    {
        $tradeoffs = Tradeoff::query()
            ->whereIn('status', [TradeoffStatus::Open, TradeoffStatus::Closed])
            ->withCount(['items', 'answers'])
            ->with('theme')
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->latest()
            ->get();

        return view('tradeoffs.index', ['tradeoffs' => $tradeoffs]);
    }

    public function show(Request $request, Tradeoff $tradeoff): View
    {
        Gate::authorize('view', $tradeoff);

        $tradeoff->load(['items.proposal.theme', 'theme']);

        return view('tradeoffs.show', ['tradeoff' => $tradeoff]);
    }

    public function results(Request $request, Tradeoff $tradeoff, TradeoffService $service): View
    {
        Gate::authorize('view', $tradeoff);

        return view('tradeoffs.results', ['tradeoff' => $tradeoff->load('theme'), 'results' => $service->results($tradeoff)]);
    }
}
