<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Recherche plein texte (Meilisearch via Scout) sur les fiches publiées. */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $themeId = $request->integer('theme') ?: null;

        $results = null;

        if (mb_strlen($query) >= 2) {
            $search = Proposal::search(mb_substr($query, 0, 200));

            if ($themeId !== null) {
                $search->where('theme_id', $themeId);
            }

            $results = $search->paginate(20);

            if ($results instanceof LengthAwarePaginator) {
                $results->withQueryString();
                $collection = $results->getCollection();

                if ($collection instanceof Collection) {
                    $collection->load(['theme', 'author']);
                }
            }
        }

        return view('search', [
            'query' => $query,
            'themeId' => $themeId,
            'themes' => Theme::query()->listed()->roots()->orderBy('position')->orderBy('name')->get(),
            'results' => $results,
        ]);
    }
}
