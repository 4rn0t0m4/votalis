<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function show(Request $request, Proposal $proposal, ?string $slug = null): View|RedirectResponse
    {
        if ($proposal->status !== ProposalStatus::Published && ! $request->user()?->can('moderate')) {
            abort(404);
        }

        if ($slug !== $proposal->slug()) {
            return redirect()->to($proposal->url(), 301);
        }

        $proposal->load(['theme.parent', 'author', 'sources', 'revisions.author']);

        return view('proposals.show', ['proposal' => $proposal]);
    }

    public function create(): View
    {
        Gate::authorize('create', Proposal::class);

        return view('proposals.create');
    }

    public function edit(Proposal $proposal): View
    {
        Gate::authorize('update', $proposal);

        return view('proposals.edit', ['proposal' => $proposal]);
    }
}
