<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TelegramBotAuthController;
use App\Http\Controllers\TestPdfController;
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

// Telegram Bot Webhook (excluded from CSRF in VerifyCsrfToken middleware)
Route::post('/telegram/webhook', [TelegramBotAuthController::class, 'webhook'])
    ->name('telegram.webhook');

// Telegram login via token (performs actual login with session)
Route::get('/auth/telegram/login/{token}', [TelegramBotAuthController::class, 'login'])
    ->name('telegram.login');

// Auth pages (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

// OAuth - Telegram callback MUST be before generic {provider} routes
Route::get('/auth/telegram/callback', [SocialAuthController::class, 'telegramCallback'])
    ->name('auth.telegram.callback');

Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.social.callback');

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

// Test pages for PDF parsing (public for development)
Route::prefix('test')->group(function () {
    // Index page with all topics
    Route::get('/', [TestPdfController::class, 'index'])->name('test.index');

    // Static parsed pages
    Route::get('/6', [TestPdfController::class, 'topic06'])->name('test.topic06');
    Route::get('/7', [TestPdfController::class, 'topic07'])->name('test.topic07');
    Route::get('/8', [TestPdfController::class, 'topic08'])->name('test.topic08');
    Route::get('/9', [TestPdfController::class, 'topic09'])->name('test.topic09');
    Route::get('/10', [TestPdfController::class, 'topic10'])->name('test.topic10');
    Route::get('/11', [TestPdfController::class, 'topic11'])->name('test.topic11');
    Route::get('/12', [TestPdfController::class, 'topic12'])->name('test.topic12');
    Route::get('/13', [TestPdfController::class, 'topic13'])->name('test.topic13');
    Route::get('/14', [TestPdfController::class, 'topic14'])->name('test.topic14');
    Route::get('/15', [TestPdfController::class, 'topic15'])->name('test.topic15');
    Route::get('/new/15', [TestPdfController::class, 'topic15Interactive'])->name('test.topic15.interactive');
    Route::get('/16', [TestPdfController::class, 'topic16'])->name('test.topic16');
    Route::get('/17', [TestPdfController::class, 'topic17'])->name('test.topic17');
    Route::get('/18', [TestPdfController::class, 'topic18'])->name('test.topic18');
    Route::get('/19', [TestPdfController::class, 'topic19'])->name('test.topic19');

    // PDF Parser Web Interface
    Route::get('/pdf', [TestPdfController::class, 'pdfParserIndex'])->name('test.pdf.index');
    Route::post('/pdf/upload', [TestPdfController::class, 'uploadPdf'])->name('test.pdf.upload');
    Route::get('/pdf/json/{topicId}', [TestPdfController::class, 'downloadJson'])->name('test.pdf.download-json');

    // Dynamic parsed pages
    Route::get('/parsed/{topicId}', [TestPdfController::class, 'showParsedPage'])->name('test.parsed');

    // Test Generator
    Route::get('/generator', [TestPdfController::class, 'testGenerator'])->name('test.generator');
    Route::post('/generator/generate', [TestPdfController::class, 'generateRandomTest'])->name('test.generator.generate');

    // OGE Variant Generator (tasks 6-19)
    Route::get('/oge', [TestPdfController::class, 'ogeGenerator'])->name('test.oge.generator');
    Route::post('/oge/generate', [TestPdfController::class, 'generateOgeVariant'])->name('test.oge.generate');

    // Legacy
    Route::post('/parse-pdf', [TestPdfController::class, 'parsePdf'])->name('test.parsePdf');
});
