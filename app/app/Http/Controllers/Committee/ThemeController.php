<?php

namespace App\Http\Controllers\Committee;

use App\Enums\ThemeIcon;
use App\Enums\ThemeStatus;
use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Administration des thèmes par le comité éditorial. Pas de suppression : un thème s'archive. */
class ThemeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', Theme::class);

        $themes = Theme::query()->roots()->with('children')->withCount('proposals')->orderBy('position')->orderBy('name')->get();

        return view('committee.themes.index', ['themes' => $themes]);
    }

    public function create(): View
    {
        Gate::authorize('create', Theme::class);

        return view('committee.themes.form', ['theme' => new Theme, 'parents' => $this->parentOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Theme::class);

        $data = $this->validated($request);

        $theme = Theme::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug((string) $data['name']),
            'description' => $data['description'],
            'icon' => $data['icon'],
            'status' => $data['status'],
            'position' => $data['position'],
            'parent_id' => $data['parent_id'],
        ]);

        return redirect()->route('committee.themes.index')->with('status', "Thème « {$theme->name} » créé.");
    }

    public function edit(Theme $theme): View
    {
        Gate::authorize('update', $theme);

        return view('committee.themes.form', ['theme' => $theme, 'parents' => $this->parentOptions($theme)]);
    }

    public function update(Request $request, Theme $theme): RedirectResponse
    {
        Gate::authorize('update', $theme);

        $data = $this->validated($request, $theme);
        $theme->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'status' => $data['status'],
            'position' => $data['position'],
            'parent_id' => $data['parent_id'],
        ]);

        return redirect()->route('committee.themes.index')->with('status', "Thème « {$theme->name} » mis à jour.");
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validated(Request $request, ?Theme $theme = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', Rule::enum(ThemeIcon::class)],
            'status' => ['required', Rule::enum(ThemeStatus::class)],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:themes,id'],
        ], [], [
            'name' => 'nom',
            'description' => 'description',
            'icon' => 'pictogramme',
            'status' => 'statut',
            'position' => 'ordre',
            'parent_id' => 'thème parent',
        ]);

        $data['position'] = $data['position'] ?? 0;
        $data['description'] = $data['description'] ?? null;
        $data['icon'] = ($data['icon'] ?? null) ?: null;
        $data['parent_id'] = $data['parent_id'] ?? null;

        if ($data['parent_id'] !== null) {
            /** @var Theme $parent */
            $parent = Theme::query()->findOrFail($data['parent_id']);

            // Deux niveaux maximum (CDC 4.1).
            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages(['parent_id' => ['Un sous-thème ne peut pas avoir lui-même de sous-thèmes : deux niveaux au maximum.']]);
            }

            if ($theme !== null && ($parent->id === $theme->id || $theme->children()->exists())) {
                throw ValidationException::withMessages(['parent_id' => ['Ce thème a déjà des sous-thèmes : il ne peut pas devenir un sous-thème.']]);
            }
        }

        return $data;
    }

    /**
     * @return Collection<int, Theme>
     */
    private function parentOptions(?Theme $except = null): Collection
    {
        $query = Theme::query()->roots()->orderBy('position')->orderBy('name');

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        return $query->get();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'theme';
        $slug = $base;
        $i = 2;

        while (Theme::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
