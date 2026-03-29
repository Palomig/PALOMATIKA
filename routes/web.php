<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TelegramBotAuthController;
use App\Http\Controllers\ParentAppController;
use App\Http\Controllers\ParentAuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\EgeController;
use App\Http\Controllers\JarvisMaterialPageController;
use App\Http\Controllers\AdminTaskAnswerController;
use App\Http\Controllers\AdminTaskStatusController;
use App\Http\Controllers\MiniAppAdminController;
use App\Http\Controllers\MiniAppAuthController;
use App\Http\Controllers\MiniAppBillingController;
use App\Http\Controllers\MiniAppStudentController;
use App\Http\Controllers\MiniAppTeacherController;
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

// Landing (browser only — Telegram Mini App uses /tg/)
Route::get('/', function () {
    return view('welcome');
})->name('landing');

// QA Reports (admin only)
Route::get('/qa-reports/{path?}', function (string $path = 'index.html') {
    $basePath = public_path('qa-reports');
    $filePath = realpath($basePath . '/' . $path);
    if (!$filePath || !str_starts_with($filePath, $basePath) || !file_exists($filePath)) {
        abort(404);
    }
    $mime = match (pathinfo($filePath, PATHINFO_EXTENSION)) {
        'html' => 'text/html',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'css' => 'text/css',
        'js' => 'application/javascript',
        default => 'application/octet-stream',
    };
    return response()->file($filePath, ['Content-Type' => $mime]);
})->where('path', '.*')->middleware(['auth', 'role:admin']);

Route::get('/materials', [JarvisMaterialPageController::class, 'index'])->name('materials.index');
Route::get('/materials/{slug}', [JarvisMaterialPageController::class, 'show'])->name('materials.show');


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

Route::prefix('learn')->name('learn.')->group(function () {
    Route::get('/fractions', fn() => view('learn.fractions'))->name('fractions');
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
    Route::get('/oge/placement', [TestPdfController::class, 'showPlacementTest'])->name('oge.placement');
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
    Route::patch('/{topicId}/status', [AdminTaskStatusController::class, 'update']);
    Route::patch('/{topicId}/status/bulk', [AdminTaskStatusController::class, 'bulkUpdate']);
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
    Route::post('/attempts/{attempt}/tasks/{taskNumber}/photo', [OgeAttemptController::class, 'uploadPhoto'])->name('api.oge.attempt.photo');
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

// Public demo: diagnostic test preview (no auth)
Route::get('/demo/diagnostic', [\App\Http\Controllers\DiagnosticDemoController::class, 'show'])->name('demo.diagnostic');
Route::post('/demo/diagnostic', [\App\Http\Controllers\DiagnosticDemoController::class, 'check'])->name('demo.diagnostic.check');
Route::get('/demo/diagnostic/review', [\App\Http\Controllers\DiagnosticDemoController::class, 'review'])->name('demo.diagnostic.review');
Route::post('/demo/diagnostic/review', [\App\Http\Controllers\DiagnosticDemoController::class, 'saveReview'])->name('demo.diagnostic.review.save');

Route::get('/kanban', [BoardController::class, 'kanban'])->name('board.kanban');
Route::get('/roadmap', [BoardController::class, 'roadmap'])->name('board.roadmap');
Route::get('/forstas', [BoardController::class, 'architecture'])->name('board.architecture');

// ========================================================================
// Telegram Mini App Routes (/tg/*)
// ========================================================================
Route::prefix('tg')->group(function () {
    // Public: home/landing (no auth required)
    Route::get('/', [MiniAppAuthController::class, 'home'])->name('miniapp.home');

    // Server-side auth: form POST with initData → verify HMAC → login → bridge redirect
    // CSRF exempted (uses Telegram HMAC instead), see VerifyCsrfToken
    Route::post('/auth', [MiniAppAuthController::class, 'authenticate'])->name('miniapp.auth');
    Route::post('/auth/bridge-ping', [MiniAppAuthController::class, 'authBridgePing'])->name('miniapp.auth.bridge_ping');
    Route::get('/auth/continue', [MiniAppAuthController::class, 'authContinue'])->name('miniapp.auth.continue');

    // Onboarding submit is public endpoint with one-time onboarding token fallback
    // (needed for unstable WebView session continuity on some VPN/mobile paths).
    Route::post('/onboarding', [MiniAppAuthController::class, 'saveOnboarding'])->name('miniapp.onboarding.save');

    // Authenticated Mini App routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/mode/{role}', [MiniAppAuthController::class, 'switchMode'])
            ->where('role', 'student|teacher')
            ->name('miniapp.mode.switch');

        // Onboarding page
        Route::get('/onboarding', [MiniAppAuthController::class, 'onboarding'])->name('miniapp.onboarding');

        // Routes that require completed onboarding
        Route::middleware([EnsureOnboardingComplete::class])->group(function () {
            Route::get('/dashboard', [MiniAppStudentController::class, 'dashboard'])->name('miniapp.dashboard');
            Route::get('/diagnostic', [MiniAppStudentController::class, 'diagnostic'])->name('miniapp.diagnostic');
            Route::post('/diagnostic/submit', [MiniAppStudentController::class, 'submitDiagnostic'])->name('miniapp.diagnostic.submit');
            Route::get('/diagnostic/results', [MiniAppStudentController::class, 'diagnosticResults'])->name('miniapp.diagnostic.results');
            Route::get('/mini', [MiniAppStudentController::class, 'mini'])->name('miniapp.mini');
            Route::get('/new-tasks', [MiniAppStudentController::class, 'newTasks'])->name('miniapp.new_tasks');
            Route::get('/part2', [MiniAppStudentController::class, 'part2'])->name('miniapp.part2');
            Route::get('/tasks-part1', [MiniAppStudentController::class, 'tasksPart1'])->name('miniapp.tasks_part1');
            Route::post('/mini/start', [MiniAppStudentController::class, 'startMini'])->name('miniapp.mini.start');
            Route::post('/full/start', [MiniAppStudentController::class, 'startFull'])->name('miniapp.full.start');
            Route::get('/test/{attemptId}', [MiniAppStudentController::class, 'test'])->name('miniapp.test');
            Route::get('/results/{attemptId}', [MiniAppStudentController::class, 'results'])->name('miniapp.results');
            Route::get('/history', [MiniAppStudentController::class, 'history'])->name('miniapp.history');
            Route::get('/history/{attemptId}', [MiniAppStudentController::class, 'historyDetail'])->name('miniapp.history.detail');
            Route::get('/tutor', [MiniAppStudentController::class, 'tutor'])->name('miniapp.tutor');
            Route::get('/homework', [MiniAppStudentController::class, 'studentHomework'])->name('miniapp.homework');

            Route::get('/profile', [MiniAppStudentController::class, 'profile'])->name('miniapp.profile');
            Route::post('/premium/trial', [MiniAppBillingController::class, 'activateTrial'])->name('miniapp.premium.trial');
            Route::post('/premium/buy', [MiniAppBillingController::class, 'buyPremium'])->name('miniapp.premium.buy');
            Route::post('/premium/payout', [MiniAppBillingController::class, 'requestPayout'])->name('miniapp.premium.payout');
            Route::post('/gift/seen', [MiniAppBillingController::class, 'giftSeen'])->name('miniapp.gift.seen');

            Route::middleware(['role:teacher,admin'])->prefix('teacher')->name('miniapp.teacher.')->group(function () {
                Route::get('/', fn () => redirect('/tg/teacher/dashboard'))->name('home');
                Route::get('/dashboard', [MiniAppTeacherController::class, 'teacherDashboard'])->name('dashboard');
                Route::get('/lessons', [MiniAppTeacherController::class, 'teacherLessons'])->name('lessons');
                Route::get('/students', [MiniAppTeacherController::class, 'teacherStudents'])->name('students');
                Route::get('/students/{studentId}', [MiniAppTeacherController::class, 'teacherStudentProfile'])->name('students.profile');
                Route::get('/students/{studentId}/attempt/{attemptId}', [MiniAppTeacherController::class, 'teacherStudentAttemptDetail'])->name('students.attempt');
                Route::post('/students/{studentId}/ownership', [MiniAppTeacherController::class, 'toggleTeacherStudentOwnership'])->name('students.ownership');
                Route::patch('/students/{studentId}/alias', [MiniAppTeacherController::class, 'updateTeacherStudentAlias'])->name('students.alias');
                Route::get('/variants', [MiniAppTeacherController::class, 'teacherVariants'])->name('variants');
                Route::get('/homework', [MiniAppTeacherController::class, 'teacherHomework'])->name('homework');
                Route::post('/homework/assign', [MiniAppTeacherController::class, 'assignHomework'])->name('homework.assign');
                Route::patch('/students/{studentId}/link', [MiniAppTeacherController::class, 'updateStudentLink'])->name('students.link');
                Route::get('/referrals', [MiniAppTeacherController::class, 'teacherReferrals'])->name('referrals');
                Route::get('/diagnostic-editor', [\App\Http\Controllers\DiagnosticEditorController::class, 'index'])->name('diagnostic.editor');
                Route::post('/diagnostic-editor/save', [\App\Http\Controllers\DiagnosticEditorController::class, 'save'])->name('diagnostic.editor.save');
                Route::get('/diagnostic-editor/tasks', [\App\Http\Controllers\DiagnosticEditorController::class, 'browseTasks'])->name('diagnostic.editor.tasks');
            });
        });

        // Admin routes for curated variants
        Route::middleware(['role:teacher,admin'])->prefix('admin')->group(function () {
            Route::get('/variants', [MiniAppAdminController::class, 'adminVariants'])->name('miniapp.admin.variants');
            Route::post('/variants/create', [MiniAppAdminController::class, 'createCuratedVariant'])->name('miniapp.admin.variants.create');
        });
    });
});


// Маршруты приложения для родителей (/parent/*)
Route::prefix('parent')->group(function () {
    Route::get('/', [ParentAuthController::class, 'home'])->name('parent.home');
    Route::post('/auth', [ParentAuthController::class, 'authenticate'])->name('parent.auth');
    Route::get('/auth/continue', [ParentAuthController::class, 'authContinue'])->name('parent.auth.continue');

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [ParentAppController::class, 'dashboard'])->name('parent.dashboard');
        Route::get('/child/{studentId}/skills', [ParentAppController::class, 'childSkills'])->name('parent.child.skills');
        Route::get('/child/{studentId}/attendance', [ParentAppController::class, 'childAttendance'])->name('parent.child.attendance');
        Route::get('/child/{studentId}/homework', [ParentAppController::class, 'childHomework'])->name('parent.child.homework');
    });
});

