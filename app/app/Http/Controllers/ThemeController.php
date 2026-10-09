<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Theme;
use App\Services\Rankings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function index(): View
    {
        $themes = Theme::query()
            ->listed()
            ->roots()
            ->with(['children' => fn ($q) => $q->listed()->withCount(['proposals' => fn ($q) => $q->published()])])
            ->withCount(['proposals' => fn ($q) => $q->published()])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('themes.index', ['themes' => $themes]);
    }

    public function show(Request $request, Theme $theme, Rankings $rankings): View
    {
        $theme->load(['parent', 'children' => fn ($q) => $q->listed()]);

        $tab = (string) $request->query('classement', 'recentes');

        if (! in_array($tab, Rankings::TABS, true)) {
            $tab = 'recentes';
        }

        $themeIds = $theme->children->pluck('id')->push($theme->id);

        $proposals = $tab === 'recentes'
            ? Proposal::query()
                ->published()
                ->whereIn('theme_id', $themeIds)
                ->with(['theme', 'author'])
                ->withCount(['arguments' => fn ($q) => $q->published()])
                ->latest()
                ->paginate(20)
                ->withQueryString()
            : null;

        $pending = $rankings->pending($tab);
        $ranked = $tab !== 'recentes' && $pending === null ? $rankings->forTheme($theme, $tab) : collect();

        return view('themes.show', [
            'theme' => $theme,
            'proposals' => $proposals,
            'ranked' => $ranked,
            'tab' => $tab,
            'tabs' => Rankings::labels(),
            'pending' => $pending,
        ]);
    }
}
