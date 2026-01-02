<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing
Route::get('/', function () {
    return view('welcome');
})->name('landing');

// OAuth
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.social.callback');
Route::get('/auth/telegram/callback', [SocialAuthController::class, 'telegramCallback'])
    ->name('auth.telegram.callback');

// Referral tracking
Route::get('/ref/{code}', function ($code) {
    session(['referral_code' => $code]);
    return redirect()->route('landing');
})->name('referral.track');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
