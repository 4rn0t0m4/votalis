<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountEraser;
use App\Services\AccountExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/** Mes données : export JSON à la demande et suppression du compte en libre-service (CDC section 8). */
class DataController extends Controller
{
    public function show(): View
    {
        return view('account.data');
    }

    public function export(Request $request, AccountExporter $exporter): Response
    {
        /** @var User $user */
        $user = $request->user();

        $json = json_encode($exporter->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="mes-donnees-'.now()->format('Y-m-d').'.json"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function confirmDeletion(): View
    {
        return view('account.delete');
    }

    public function destroy(Request $request, AccountEraser $eraser): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['accepted'],
        ], [
            'current_password.current_password' => 'Le mot de passe est incorrect.',
            'confirmation.accepted' => 'Cochez la case pour confirmer la suppression.',
        ]);

        // Déconnexion avant la suppression : la rotation du jeton « se souvenir de moi » par
        // logout() enregistrerait sinon le modèle supprimé, ce qui recréerait la ligne.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $eraser->erase($user, 'self-service');

        return redirect()->route('home')->with('status', __('Votre compte et vos votes ont été supprimés. Vos propositions et arguments publiés restent en ligne, rattachés à « participant supprimé ».'));
    }
}
