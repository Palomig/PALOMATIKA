<?php

use App\Http\Controllers\Pwa\AuthController;
use App\Http\Controllers\Pwa\StudentController;
use App\Http\Controllers\Pwa\TeacherController;
use App\Http\Controllers\Pwa\ManifestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| student.palomatika.ru
|--------------------------------------------------------------------------
*/
Route::domain('student.' . config('app.base_domain'))->group(function () {

    // PWA assets
    Route::get('/manifest.json', [ManifestController::class, 'student'])->name('pwa.student.manifest');
    Route::get('/sw.js', [ManifestController::class, 'serviceWorker'])->name('pwa.sw');

    // Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('pwa.student.login');
    Route::get('/auth/{provider}', [AuthController::class, 'redirect'])->name('pwa.student.auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('pwa.student.auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('pwa.student.logout');

    // Onboarding (no auth required — new users land here)
    Route::get('/onboarding', [StudentController::class, 'onboarding'])->name('pwa.student.onboarding');
    Route::post('/onboarding', [StudentController::class, 'saveOnboarding'])->name('pwa.student.onboarding.save');

    // Migration from Telegram
    Route::get('/migrate', [AuthController::class, 'migrateFromTelegram'])->name('pwa.student.migrate');

    // Protected student routes
    Route::middleware(['auth', 'pwa.onboarding'])->group(function () {
        Route::get('/', [StudentController::class, 'dashboard'])->name('pwa.student.dashboard');
        Route::get('/mini', [StudentController::class, 'mini'])->name('pwa.student.mini');
        Route::get('/new-tasks', [StudentController::class, 'newTasks'])->name('pwa.student.new-tasks');
        Route::get('/part2', [StudentController::class, 'part2'])->name('pwa.student.part2');
        Route::get('/tasks-part1', [StudentController::class, 'tasksPart1'])->name('pwa.student.tasks-part1');
        Route::post('/mini/start', [StudentController::class, 'startMini'])->name('pwa.student.mini.start');
        Route::post('/full/start', [StudentController::class, 'startFull'])->name('pwa.student.full.start');
        Route::get('/test/{attemptId}', [StudentController::class, 'test'])->name('pwa.student.test');
        Route::get('/results/{attemptId}', [StudentController::class, 'results'])->name('pwa.student.results');
        Route::get('/history', [StudentController::class, 'history'])->name('pwa.student.history');
        Route::get('/history/{attemptId}', [StudentController::class, 'historyDetail'])->name('pwa.student.history.detail');
        Route::get('/profile', [StudentController::class, 'profile'])->name('pwa.student.profile');
        Route::get('/homework', [StudentController::class, 'studentHomework'])->name('pwa.student.homework');
        Route::get('/tutor', [StudentController::class, 'tutor'])->name('pwa.student.tutor');
    });
});

/*
|--------------------------------------------------------------------------
| teacher.palomatika.ru
|--------------------------------------------------------------------------
*/
Route::domain('teacher.' . config('app.base_domain'))->group(function () {

    // PWA assets
    Route::get('/manifest.json', [ManifestController::class, 'teacher'])->name('pwa.teacher.manifest');
    Route::get('/sw.js', [ManifestController::class, 'serviceWorker'])->name('pwa.sw.teacher');

    // Auth
    Route::get('/login', [AuthController::class, 'showTeacherLogin'])->name('pwa.teacher.login');
    Route::get('/auth/{provider}', [AuthController::class, 'redirectTeacher'])->name('pwa.teacher.auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callbackTeacher'])->name('pwa.teacher.auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('pwa.teacher.logout');

    // Protected teacher routes
    Route::middleware(['auth', 'role:teacher,admin'])->group(function () {
        Route::get('/', fn() => redirect()->route('pwa.teacher.dashboard'));
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('pwa.teacher.dashboard');
        Route::get('/students', [TeacherController::class, 'students'])->name('pwa.teacher.students');
        Route::get('/students/{studentId}', [TeacherController::class, 'studentProfile'])->name('pwa.teacher.student.profile');
        Route::get('/students/{studentId}/attempt/{attemptId}', [TeacherController::class, 'studentAttemptDetail'])->name('pwa.teacher.student.attempt');
        Route::post('/students/{studentId}/ownership', [TeacherController::class, 'toggleOwnership'])->name('pwa.teacher.student.ownership');
        Route::patch('/students/{studentId}/alias', [TeacherController::class, 'updateAlias'])->name('pwa.teacher.student.alias');
        Route::patch('/students/{studentId}/link', [TeacherController::class, 'updateStudentLink'])->name('pwa.teacher.student.link');
        Route::get('/lessons', [TeacherController::class, 'lessons'])->name('pwa.teacher.lessons');
        Route::get('/homework', [TeacherController::class, 'homework'])->name('pwa.teacher.homework');
        Route::post('/homework/assign', [TeacherController::class, 'assignHomework'])->name('pwa.teacher.homework.assign');
        Route::get('/variants', [TeacherController::class, 'variants'])->name('pwa.teacher.variants');
        Route::get('/referrals', [TeacherController::class, 'referrals'])->name('pwa.teacher.referrals');
    });
});
