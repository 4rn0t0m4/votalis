<?php

use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Committee\ThemeController as CommitteeThemeController;
use App\Http\Controllers\Committee\TradeoffController as CommitteeTradeoffController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TradeoffController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/comment-ca-marche', 'pages.how-it-works')->name('how-it-works');

// Thèmes et propositions : lecture publique.
Route::get('/recherche', SearchController::class)->name('search');
Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
Route::get('/themes/{theme:slug}', [ThemeController::class, 'show'])->name('themes.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/vote-rapide', 'quick-vote')->name('quick-vote');
    Route::get('/propositions/nouvelle', [ProposalController::class, 'create'])->name('proposals.create');
    Route::get('/propositions/{proposal}/modifier', [ProposalController::class, 'edit'])->whereNumber('proposal')->name('proposals.edit');
});

Route::get('/propositions/{proposal}/{slug?}', [ProposalController::class, 'show'])->whereNumber('proposal')->name('proposals.show');

// Arbitrages : lecture publique, réponse réservée aux participants (composant Livewire).
Route::get('/arbitrages', [TradeoffController::class, 'index'])->name('tradeoffs.index');
Route::get('/arbitrages/{tradeoff}', [TradeoffController::class, 'show'])->name('tradeoffs.show');
Route::get('/arbitrages/{tradeoff}/resultats', [TradeoffController::class, 'results'])->name('tradeoffs.results');

Route::middleware(['auth', 'verified'])->prefix('mon-compte')->name('account.')->group(function () {
    Route::view('/', 'account.show')->name('show');
});

Route::middleware('auth')->prefix('mon-compte')->group(function () {
    Route::get('/securite', [SecurityController::class, 'show'])->name('security.show');
});

// Comité éditorial.
Route::middleware(['auth', 'verified', 'can:manage-tradeoffs'])->prefix('comite')->name('committee.')->group(function () {
    Route::get('/arbitrages', [CommitteeTradeoffController::class, 'index'])->name('tradeoffs.index');
    Route::get('/arbitrages/nouveau', [CommitteeTradeoffController::class, 'create'])->name('tradeoffs.create');
    Route::post('/arbitrages', [CommitteeTradeoffController::class, 'store'])->name('tradeoffs.store');
    Route::get('/arbitrages/{tradeoff}/modifier', [CommitteeTradeoffController::class, 'edit'])->name('tradeoffs.edit');
    Route::put('/arbitrages/{tradeoff}', [CommitteeTradeoffController::class, 'update'])->name('tradeoffs.update');
    Route::post('/arbitrages/{tradeoff}/statut', [CommitteeTradeoffController::class, 'status'])->name('tradeoffs.status');
    Route::post('/arbitrages/{tradeoff}/mesures', [CommitteeTradeoffController::class, 'storeItem'])->name('tradeoffs.items.store');
    Route::delete('/arbitrages/{tradeoff}/mesures/{item}', [CommitteeTradeoffController::class, 'destroyItem'])->name('tradeoffs.items.destroy');
    Route::post('/arbitrages/{tradeoff}/suggestions/{suggestion}/ecarter', [CommitteeTradeoffController::class, 'rejectSuggestion'])->name('tradeoffs.suggestions.reject');
});

Route::middleware(['auth', 'verified', 'can:manage-themes'])->prefix('comite')->name('committee.')->group(function () {
    Route::get('/themes', [CommitteeThemeController::class, 'index'])->name('themes.index');
    Route::get('/themes/nouveau', [CommitteeThemeController::class, 'create'])->name('themes.create');
    Route::post('/themes', [CommitteeThemeController::class, 'store'])->name('themes.store');
    Route::get('/themes/{theme}/modifier', [CommitteeThemeController::class, 'edit'])->name('themes.edit');
    Route::put('/themes/{theme}', [CommitteeThemeController::class, 'update'])->name('themes.update');
});
