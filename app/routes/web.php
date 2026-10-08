<?php

use App\Http\Controllers\Account\DataController;
use App\Http\Controllers\Account\ModerationController as AccountModerationController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Committee\ThemeController as CommitteeThemeController;
use App\Http\Controllers\Committee\TradeoffController as CommitteeTradeoffController;
use App\Http\Controllers\Moderation\AppealController;
use App\Http\Controllers\Moderation\CaseController;
use App\Http\Controllers\Moderation\QueueController;
use App\Http\Controllers\Moderation\SignalController;
use App\Http\Controllers\ModerationLogController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TradeoffController;
use App\Http\Controllers\TransparencyController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/comment-ca-marche', 'pages.how-it-works')->name('how-it-works');
Route::view('/comment-fonctionne-le-classement', 'pages.ranking')->name('ranking-explained');
Route::view('/charte-de-moderation', 'pages.charter')->name('charter');
Route::get('/transparence', [TransparencyController::class, 'index'])->name('transparency');
Route::view('/confidentialite', 'pages.legal.privacy')->name('privacy');
Route::view('/mentions-legales', 'pages.legal.notice')->name('legal-notice');
Route::view('/cookies', 'pages.legal.cookies')->name('cookies');

// Journal public de modération : lecture libre.
Route::get('/journal-de-moderation', [ModerationLogController::class, 'index'])->name('moderation-log.index');
Route::get('/journal-de-moderation/{entry}', [ModerationLogController::class, 'show'])->whereNumber('entry')->name('moderation-log.show');

// Thèmes et propositions : lecture publique.
Route::get('/recherche', SearchController::class)->name('search');
Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
Route::get('/themes/{theme:slug}', [ThemeController::class, 'show'])->name('themes.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/vote-rapide', 'quick-vote')->name('quick-vote');
    Route::get('/propositions/nouvelle', [ProposalController::class, 'create'])->name('proposals.create');
    Route::get('/propositions/{proposal}/modifier', [ProposalController::class, 'edit'])->whereNumber('proposal')->name('proposals.edit');
    Route::get('/signaler/{type}/{id}', [ReportController::class, 'create'])->whereNumber('id')->name('reports.create');
    Route::post('/signaler/{type}/{id}', [ReportController::class, 'store'])->whereNumber('id')->name('reports.store');
});

// Signaux d'intégrité : modération, comité et administrateur technique (lecture seule pour ce dernier).
Route::middleware(['auth', 'verified', 'can:view-integrity-signals'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/signaux', [SignalController::class, 'index'])->name('signals.index');
    Route::post('/signaux/{signal}', [SignalController::class, 'update'])->name('signals.update');
});

// Espace de modération (modérateurs et comité éditorial, second facteur exigé par le middleware global).
Route::middleware(['auth', 'verified', 'can:moderate'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('queue');
    Route::get('/dossiers/{type}/{id}', [CaseController::class, 'show'])->whereNumber('id')->name('case');
    Route::post('/dossiers/{type}/{id}/conserver', [CaseController::class, 'keep'])->whereNumber('id')->name('case.keep');
    Route::post('/dossiers/{type}/{id}/masquer', [CaseController::class, 'hide'])->whereNumber('id')->name('case.hide');
    Route::post('/dossiers/{type}/{id}/reformulation', [CaseController::class, 'requestRewrite'])->whereNumber('id')->name('case.rewrite');

    Route::middleware('can:arbitrate-appeals')->group(function () {
        Route::post('/dossiers/{type}/{id}/suspendre', [CaseController::class, 'suspend'])->whereNumber('id')->name('case.suspend');
        Route::get('/contestations', [AppealController::class, 'index'])->name('appeals.index');
        Route::get('/contestations/{appeal}', [AppealController::class, 'show'])->name('appeals.show');
        Route::post('/contestations/{appeal}', [AppealController::class, 'decide'])->name('appeals.decide');
    });
});

Route::get('/propositions/{proposal}/{slug?}', [ProposalController::class, 'show'])->whereNumber('proposal')->name('proposals.show');

// Arbitrages : lecture publique, réponse réservée aux participants (composant Livewire).
Route::get('/arbitrages', [TradeoffController::class, 'index'])->name('tradeoffs.index');
Route::get('/arbitrages/{tradeoff}', [TradeoffController::class, 'show'])->name('tradeoffs.show');
Route::get('/arbitrages/{tradeoff}/resultats', [TradeoffController::class, 'results'])->name('tradeoffs.results');

Route::middleware(['auth', 'verified'])->prefix('mon-compte')->name('account.')->group(function () {
    Route::view('/', 'account.show')->name('show');
    Route::get('/moderation', [AccountModerationController::class, 'index'])->name('moderation.index');
    Route::get('/moderation/contester/{entry}', [AccountModerationController::class, 'create'])->whereNumber('entry')->name('moderation.appeal');
    Route::post('/moderation/contester/{entry}', [AccountModerationController::class, 'store'])->whereNumber('entry')->name('moderation.appeal.store');
    Route::get('/donnees', [DataController::class, 'show'])->name('data.show');
    Route::post('/donnees/export', [DataController::class, 'export'])->name('data.export');
    Route::get('/suppression', [DataController::class, 'confirmDeletion'])->name('data.delete');
    Route::delete('/', [DataController::class, 'destroy'])->name('data.destroy');
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
