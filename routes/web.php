<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TelegramBotAuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\EgeController;
use App\Http\Controllers\JarvisMaterialPageController;
use App\Http\Controllers\AdminTaskAnswerController;
use App\Http\Controllers\MiniAppController;
use App\Http\Controllers\OgeAttemptController;
use App\Http\Controllers\OgeTemplateController;
use App\Http\Controllers\RepetitorController;
use App\Http\Controllers\Teacher\AuditController as TeacherAuditController;
use App\Http\Controllers\Teacher\StudentsController;
use App\Http\Controllers\TestPdfController;
use App\Http\Controllers\Teacher\StudentGroupController;
use App\Http\Controllers\Teacher\OgeReviewController;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing
Route::get('/', function () {
    $lastAttempt = null;
    $nextVariantHash = null;

    if (Auth::check()) {
        $userId = Auth::id();

        $lastAttempt = \App\Models\OgeAttempt::where('student_id', $userId)
            ->where('status', 'scored')
            ->latest('submitted_at')
            ->with(['variant', 'scorings'])
            ->first();

        $solvedVariantIds = \App\Models\OgeAttempt::where('student_id', $userId)
            ->whereIn('status', ['scored', 'submitted'])
            ->pluck('variant_id');

        $nextVariant = \App\Models\OgeVariant::whereNotIn('id', $solvedVariantIds)
            ->orderBy('id')
            ->first();

        $nextVariantHash = $nextVariant?->hash;
    }

    return view('welcome', compact('lastAttempt', 'nextVariantHash'));
})->name('landing');

Route::get('/materials', [JarvisMaterialPageController::class, 'index'])->name('materials.index');
Route::get('/materials/{slug}', [JarvisMaterialPageController::class, 'show'])->name('materials.show');

// Meal Plan (SmartCart)
Route::get('/meal-plan', function () {
    $json = file_get_contents(public_path('smartcart/data/meal-plan.json'));
    $data = json_decode($json, true);
    return view('meal-plan', ['data' => $data]);
})->name('meal-plan');

// Telegram Bot Webhook (excluded from CSRF in VerifyCsrfToken middleware)
Route::post('/telegram/webhook', [TelegramBotAuthController::class, 'webhook'])
    ->name('telegram.webhook');

// Telegram login via token (performs actual login with session)
Route::get('/auth/telegram/login/{token}', [TelegramBotAuthController::class, 'login'])
    ->name('telegram.login');

// Telegram Mini App instant auth (session-backed JSON endpoint)
// No 'guest' middleware — Mini App may re-auth even if session exists
Route::post('/api/auth/telegram/webapp-login', [TelegramBotAuthController::class, 'webAppLogin'])
    ->name('telegram.webapp-login');

// Auth pages (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            app(AuditLogger::class)->log([
                'event_type' => 'login_failed',
                'category' => 'auth',
                'severity' => 'warning',
                'subject_type' => 'email',
                'subject_id' => strtolower($credentials['email']),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_json' => [
                    'method' => 'password',
                ],
            ]);

            return response()->json([
                'message' => 'Неверный email или пароль',
            ], 422);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $token = null;

        // Optional API token: web session login must work even if sanctum table is missing.
        try {
            $token = $user->createToken('web-login')->plainTextToken;
        } catch (\Throwable $e) {
            Log::warning('Failed to create web-login API token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $redirectTo = redirect()->intended('/dashboard')->getTargetUrl();

        app(AuditLogger::class)->log([
            'event_type' => 'login_success',
            'category' => 'auth',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $user->role,
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'method' => 'password',
                'remember' => (bool) ($credentials['remember'] ?? false),
            ],
        ]);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'redirect_to' => $redirectTo,
        ]);
    })->name('login.attempt');

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

// Repetitor - Interactive visualizations
Route::prefix('repetitor')->name('repetitor.')->group(function () {
    Route::get('/vector', [RepetitorController::class, 'vectorAngle'])->name('vector');
});

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::post('/view-as/clear', function (Request $request) {
        abort_unless($request->user()?->role === 'admin', 403);

        app(AuditLogger::class)->log([
            'event_type' => 'view_as_cleared',
            'category' => 'admin',
            'severity' => 'info',
            'actor_user_id' => $request->user()->id,
            'actor_role' => $request->user()->role,
            'subject_type' => 'view_as_role',
            'subject_id' => (string) ($request->session()->get('view_as_role') ?? ''),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->forget('view_as_role');
        return redirect()->back()->withInput();
    })->name('view-as.clear');

    Route::post('/view-as/{role}', function (Request $request, string $role) {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless(in_array($role, ['student', 'teacher'], true), 404);

        $request->session()->put('view_as_role', $role);

        app(AuditLogger::class)->log([
            'event_type' => 'view_as_set',
            'category' => 'admin',
            'severity' => 'info',
            'actor_user_id' => $request->user()->id,
            'actor_role' => $request->user()->role,
            'subject_type' => 'view_as_role',
            'subject_id' => $role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->withInput();
    })->name('view-as.set');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        $viewAsRole = $user && $user->role === 'admin' ? session('view_as_role') : null;
        if ($viewAsRole === 'teacher') {
            return redirect()->to('/teacher');
        }

        return view('dashboard');
    })->name('dashboard');

    // Student pages (redirects to new topics system)
    Route::get('/student/topics', function () {
        return redirect()->route('topics.index');
    })->name('student.topics');

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
            $user = auth()->user();
            $viewAsRole = $user && $user->role === 'admin' ? session('view_as_role') : null;
            if ($viewAsRole === 'student') {
                return redirect()->to('/dashboard');
            }

            return view('teacher.dashboard');
        })->name('dashboard');

        Route::middleware('role:teacher,admin')->group(function () {
            Route::get('/students', [StudentsController::class, 'index'])->name('students');
            Route::get('/students/{id}', [StudentsController::class, 'show'])->name('students.show');
        });

        Route::middleware('role:teacher,admin')->prefix('groups')->name('groups.')->group(function () {
            Route::get('/', [StudentGroupController::class, 'index'])->name('index');
            Route::get('/data', [StudentGroupController::class, 'groups'])->name('data');
            Route::get('/students', [StudentGroupController::class, 'students'])->name('students');
            Route::post('/', [StudentGroupController::class, 'store'])->name('store');
            Route::post('/{group}/students', [StudentGroupController::class, 'addStudent'])->name('students.add');
            Route::delete('/{group}/students/{studentId}', [StudentGroupController::class, 'removeStudent'])->name('students.remove');
            Route::delete('/{group}', [StudentGroupController::class, 'destroy'])->name('destroy');
        });

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

        Route::middleware('role:teacher,admin')->group(function () {
            Route::get('/audit', [TeacherAuditController::class, 'index'])->name('audit.index');
            Route::get('/materials', [JarvisMaterialPageController::class, 'teacherIndex'])->name('materials.index');
        });

        Route::prefix('oge')->name('oge.')->middleware('role:teacher,admin')->group(function () {
            Route::get('/teachers', [OgeReviewController::class, 'teachers'])->name('teachers');
            Route::get('/teachers/{teacherId}/variants', [OgeReviewController::class, 'variants'])->name('variants');
            Route::get('/variants/{variantId}/results', [OgeReviewController::class, 'results'])->name('results');
        });
    });

    // Logout
    Route::post('/logout', function () {
        $user = request()->user();
        if ($user) {
            app(AuditLogger::class)->log([
                'event_type' => 'logout',
                'category' => 'auth',
                'severity' => 'info',
                'actor_user_id' => $user->id,
                'actor_role' => $user->role,
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

// New unified topic pages (JSON-based)
Route::middleware(['auth', 'role:teacher,admin'])->prefix('topics')->name('topics.')->group(function () {
    Route::get('/', [TopicController::class, 'index'])->name('index');
    Route::get('/{id}', [TopicController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/export', [TopicController::class, 'export'])->name('export')->where('id', '[0-9]+');
    // Server-side SVG rendering (new!)
    Route::get('/{id}/svg', [TopicController::class, 'showWithServerSvg'])->name('svg')->where('id', '[0-9]+');
});

// OGE Generator (new url, shared implementation with legacy test pages)
Route::middleware(['auth', 'role:teacher,admin'])->group(function () {
    Route::get('/oge', [TestPdfController::class, 'ogeGenerator'])->name('oge.generator');
});
Route::middleware(['auth', 'role:student,teacher,admin'])->group(function () {
    Route::get('/oge/{hash}', [TestPdfController::class, 'showOgeVariant'])->name('oge.show');
});

// ========================================================================
// ЕГЭ Routes (обособленная система)
// ========================================================================
Route::prefix('ege')->name('ege.')->group(function () {
    Route::get('/', [EgeController::class, 'index'])->name('index');
    Route::get('/topics/{id}', [EgeController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/generator', [EgeController::class, 'generator'])->name('generator');
    Route::get('/variant/{hash}', [EgeController::class, 'showVariant'])->name('variant');
});

// API for EGE tasks
Route::prefix('api/ege')->group(function () {
    Route::post('/save-variant', [EgeController::class, 'saveVariant'])->name('api.ege.save-variant');
    Route::get('/{topicId}/random', [EgeController::class, 'apiGetRandomTasks']);
    Route::get('/{topicId}', [EgeController::class, 'apiGetTopicData']);
});

// API for tasks
Route::prefix('api/topics')->middleware(['auth', 'role:teacher,admin'])->group(function () {
    Route::get('/{topicId}/random', [TopicController::class, 'apiGetRandomTasks']);
    Route::get('/{topicId}', [TopicController::class, 'apiGetTopicData']);
});

Route::prefix('api/topics')->middleware(['auth', 'role:admin'])->group(function () {
    Route::patch('/{topicId}/answers', [AdminTaskAnswerController::class, 'update']);
});

// API for OGE attempts (student solving flow)
Route::prefix('api/oge')->middleware(['auth', 'role:teacher,admin'])->group(function () {
    Route::get('/templates', [OgeTemplateController::class, 'index'])->name('api.oge.templates.index');
    Route::post('/templates', [OgeTemplateController::class, 'store'])->name('api.oge.templates.store');
    Route::delete('/templates/{templateId}', [OgeTemplateController::class, 'destroy'])->name('api.oge.templates.destroy');
    Route::get('/attempts/{attempt}/result', [OgeAttemptController::class, 'result'])->name('api.oge.attempt.result');
    Route::get('/attempts/{attempt}/telegram-summary', [OgeAttemptController::class, 'telegramSummary'])->name('api.oge.attempt.telegram-summary');
});

Route::prefix('api/oge')->middleware(['auth', 'role:student,admin'])->group(function () {
    Route::post('/variants/{hash}/attempt/start', [OgeAttemptController::class, 'start'])->name('api.oge.attempt.start');
    Route::get('/attempts/{attempt}/status', [OgeAttemptController::class, 'status'])->name('api.oge.attempt.status');
    Route::post('/attempts/{attempt}/tasks/{taskNumber}/focus', [OgeAttemptController::class, 'focus'])->name('api.oge.attempt.focus');
    Route::post('/attempts/{attempt}/tasks/{taskNumber}/blur', [OgeAttemptController::class, 'blur'])->name('api.oge.attempt.blur');
    Route::post('/attempts/{attempt}/tasks/{taskNumber}/commit', [OgeAttemptController::class, 'commit'])->name('api.oge.attempt.commit');
    Route::post('/attempts/{attempt}/heartbeat', [OgeAttemptController::class, 'heartbeat'])->name('api.oge.attempt.heartbeat');
    Route::post('/attempts/{attempt}/submit', [OgeAttemptController::class, 'submit'])->name('api.oge.attempt.submit');
});

Route::prefix('api/audit')->middleware(['auth', 'role:teacher,admin'])->group(function () {
    Route::get('/events', [TeacherAuditController::class, 'events'])->name('api.audit.events');
    Route::get('/events/export', [TeacherAuditController::class, 'export'])->name('api.audit.events.export');
    Route::get('/events/{event}', [TeacherAuditController::class, 'show'])->name('api.audit.events.show');
    Route::get('/meta', [TeacherAuditController::class, 'meta'])->name('api.audit.meta');
});

// ========================================================================
// Board Routes (Kanban, Roadmap, Architecture)
// ========================================================================
Route::prefix('api/board')->group(function () {
    Route::get('/tasks', [BoardController::class, 'apiGetTasks']);
    Route::get('/meta', [BoardController::class, 'apiGetMeta']);
});

Route::get('/kanban', [BoardController::class, 'kanban'])->name('board.kanban');
Route::get('/roadmap', [BoardController::class, 'roadmap'])->name('board.roadmap');
Route::get('/forstas', [BoardController::class, 'architecture'])->name('board.architecture');

// ========================================================================
// Telegram Mini App Routes (/tg/*)
// ========================================================================
Route::prefix('tg')->group(function () {
    // Public: home/landing (no auth required)
    Route::get('/', [MiniAppController::class, 'home'])->name('miniapp.home');

    // Authenticated Mini App routes
    Route::middleware(['auth'])->group(function () {
        // Onboarding
        Route::get('/onboarding', [MiniAppController::class, 'onboarding'])->name('miniapp.onboarding');
        Route::post('/onboarding', [MiniAppController::class, 'saveOnboarding'])->name('miniapp.onboarding.save');

        // Routes that require completed onboarding
        Route::middleware([EnsureOnboardingComplete::class])->group(function () {
            Route::get('/dashboard', [MiniAppController::class, 'dashboard'])->name('miniapp.dashboard');
            Route::get('/mini', [MiniAppController::class, 'mini'])->name('miniapp.mini');
            Route::post('/mini/start', [MiniAppController::class, 'startMini'])->name('miniapp.mini.start');
            Route::post('/full/start', [MiniAppController::class, 'startFull'])->name('miniapp.full.start');
            Route::get('/test/{attemptId}', [MiniAppController::class, 'test'])->name('miniapp.test');
            Route::get('/results/{attemptId}', [MiniAppController::class, 'results'])->name('miniapp.results');
            Route::get('/tutor', [MiniAppController::class, 'tutor'])->name('miniapp.tutor');
        });

        // Admin routes for curated variants
        Route::middleware(['role:teacher,admin'])->prefix('admin')->group(function () {
            Route::get('/variants', [MiniAppController::class, 'adminVariants'])->name('miniapp.admin.variants');
            Route::post('/variants/create', [MiniAppController::class, 'createCuratedVariant'])->name('miniapp.admin.variants.create');
        });
    });
});

// Test pages for PDF parsing (legacy, public for development)
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
    // Topics 15, 16, 17 — redirect to new unified system with pre-baked SVG
    Route::get('/15', fn() => redirect()->route('topics.show', 15))->name('test.topic15');
    Route::get('/16', fn() => redirect()->route('topics.show', 16))->name('test.topic16');
    Route::get('/17', fn() => redirect()->route('topics.show', 17))->name('test.topic17');
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
    Route::get('/generator/result/{hash}', [TestPdfController::class, 'showGeneratedRandomTest'])
        ->where('hash', '[a-z0-9]{8}')
        ->name('test.generator.show');

    // OGE Variant Generator (tasks 6-19)
    Route::middleware(['auth', 'role:teacher,admin'])->group(function () {
        Route::get('/oge', [TestPdfController::class, 'ogeGenerator'])->name('test.oge.generator');
        Route::post('/oge/save', [TestPdfController::class, 'saveVariant'])->name('test.oge.save');
    });
    Route::middleware(['auth', 'role:student,teacher,admin'])->group(function () {
        Route::get('/oge/{hash}', [TestPdfController::class, 'showOgeVariant'])->name('test.oge.show');
    });

    // Legacy
    Route::post('/parse-pdf', [TestPdfController::class, 'parsePdf'])->name('test.parsePdf');
});
