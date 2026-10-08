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
        $fullAccess = $proposal->status === ProposalStatus::Published
            || Gate::forUser($request->user())->allows('viewHidden', $proposal);

        // Contenu masqué : bandeau public avec le motif et le journal, sans titre ni adresse canonique si illégal.
        if (! $fullAccess) {
            return view('proposals.hidden', [
                'proposal' => $proposal,
                'entry' => $proposal->moderationEntries()->first(),
            ]);
        }

        if ($slug !== $proposal->slug()) {
            return redirect()->to($proposal->url(), 301);
        }

        $proposal->load(['theme.parent', 'author', 'sources', 'revisions.author']);

        return view('proposals.show', [
            'proposal' => $proposal,
            'moderationEntry' => $proposal->isModerated() ? $proposal->moderationEntries()->first() : null,
        ]);
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
