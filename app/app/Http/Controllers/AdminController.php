<?php

namespace App\Http\Controllers;

use App\Services\PublicPageCache;
use App\Services\ReadOnlyMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Administration technique (capacité `manage-platform`) : lecture seule, état de la plateforme. Aucun pouvoir éditorial. */
class AdminController extends Controller
{
    public function index(ReadOnlyMode $readOnly): View
    {
        return view('admin.index', [
            'readOnly' => $readOnly->enabled(),
            'message' => $readOnly->message(),
            'queued' => (int) DB::table('jobs')->count(),
            'failed' => (int) DB::table('failed_jobs')->count(),
            'commit' => config('votalis.commit'),
        ]);
    }

    public function readOnly(Request $request, ReadOnlyMode $readOnly, PublicPageCache $cache): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        if ((bool) $data['enabled']) {
            $readOnly->enable($data['message'] ?? null);
        } else {
            $readOnly->disable();
        }

        $cache->flush();

        return redirect()->route('admin.index')->with('status', (bool) $data['enabled'] ? __('Mode lecture seule activé.') : __('Mode lecture seule désactivé.'));
    }
}
