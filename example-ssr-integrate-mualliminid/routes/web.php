<?php

use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/login-sso', [SSOController::class, 'redirectToSSO'])->name('sso.login');
Route::get('/auth/callback', [SSOController::class, 'handleCallback'])->name('sso.callback');
Route::post('/logout', [SSOController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
