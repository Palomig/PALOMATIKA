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

// Auth pages (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

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

    // Student pages
    Route::get('/topics', function () {
        return view('student.topics');
    })->name('topics');

    Route::get('/topics/{id}', function ($id) {
        return view('student.topics', ['topicId' => $id]);
    })->name('topics.show');

    Route::get('/practice', function () {
        return view('student.practice');
    })->name('practice');

    Route::get('/leaderboard', function () {
        return view('student.leaderboard');
    })->name('leaderboard');

    Route::get('/badges', function () {
        return view('student.badges');
    })->name('badges');

    Route::get('/duels', function () {
        return view('student.duels');
    })->name('duels');

    // Teacher pages
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/', function () {
            return view('teacher.dashboard');
        })->name('dashboard');

        Route::get('/students', function () {
            return view('teacher.students');
        })->name('students');

        Route::get('/students/{id}', function ($id) {
            return view('teacher.students', ['studentId' => $id]);
        })->name('students.show');

        Route::get('/homework', function () {
            return view('teacher.homework');
        })->name('homework');

        Route::get('/homework/create', function () {
            return view('teacher.homework');
        })->name('homework.create');

        Route::get('/analytics', function () {
            return view('teacher.analytics');
        })->name('analytics');

        Route::get('/earnings', function () {
            return view('teacher.earnings');
        })->name('earnings');
    });

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
