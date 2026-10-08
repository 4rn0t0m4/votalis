<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Theme;
use Illuminate\Contracts\View\View;

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

    public function show(Theme $theme): View
    {
        $theme->load(['parent', 'children' => fn ($q) => $q->listed()]);

        $themeIds = $theme->children->pluck('id')->push($theme->id);

        $proposals = Proposal::query()
            ->published()
            ->whereIn('theme_id', $themeIds)
            ->with(['theme', 'author'])
            ->withCount(['arguments' => fn ($q) => $q->published()])
            ->latest()
            ->paginate(20);

        return view('themes.show', ['theme' => $theme, 'proposals' => $proposals]);
    }
}
