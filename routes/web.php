<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Only for visitors who are not logged in (a logged-in user is sent away from /login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    // max 5 attempts per minute, against password guessing
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

// Only for logged-in users
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // temporary: replaced by one dashboard per role
    Route::view('/space', 'space')->name('space');

    // TEMPORARY, to test the role middleware (delete once the real pages exist):
    // log in as each account and open these three URLs, you should get a 403 on two of them.
    Route::get('/test/admin', fn () => 'Zone admin')->middleware('role:admin');
    Route::get('/test/prof', fn () => 'Zone enseignant')->middleware('role:prof');
    Route::get('/test/etudiant', fn () => 'Zone étudiant')->middleware('role:etudiant');

    // Later, one group per role, for example:
    // Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () { ... });
    // Route::middleware('role:prof')->prefix('prof')->name('prof.')->group(function () { ... });
    // Route::middleware('role:etudiant')->prefix('etudiant')->name('etudiant.')->group(function () { ... });
});
