<?php

use App\Http\Controllers\Account\SecurityController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/comment-ca-marche', 'pages.how-it-works')->name('how-it-works');

Route::middleware(['auth', 'verified'])->prefix('mon-compte')->name('account.')->group(function () {
    Route::view('/', 'account.show')->name('show');
});

Route::middleware('auth')->prefix('mon-compte')->group(function () {
    Route::get('/securite', [SecurityController::class, 'show'])->name('security.show');
});
