<?php

use App\Http\Controllers\Pwa\AuthController;
use App\Http\Controllers\Pwa\BugReportController;
use App\Http\Controllers\Pwa\EgeStudentController;
use App\Http\Controllers\Pwa\ManifestController;
use App\Http\Controllers\Pwa\PracticeController;
use App\Http\Controllers\Pwa\StudentController;
use App\Http\Controllers\Pwa\StudentLessonController;
use App\Http\Controllers\Pwa\StudentNoteController;
use App\Http\Controllers\Pwa\TeacherController;
use App\Http\Controllers\Pwa\TeacherLessonController;
use App\Http\Controllers\Pwa\VprController;
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

    // Bug reports
    Route::post('/bug-report', [BugReportController::class, 'store'])->name('pwa.student.bug-report');

    // ОГЭ-дашборд для 8-классников (без редиректа на VPR)
    Route::get('/oge', [StudentController::class, 'ogeDashboard'])->middleware(['auth', 'pwa.onboarding', 'pwa.lesson-lock'])->name('pwa.student.oge-dashboard');

    // Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('pwa.student.login');
    Route::get('/auth/{provider}', [AuthController::class, 'redirect'])->name('pwa.student.auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('pwa.student.auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('pwa.student.logout');

    // QA-only login (secured by deploy secret, not exposed to users)
    Route::get('/qa-login', [AuthController::class, 'qaLogin'])->name('pwa.student.qa-login');

    // Onboarding (no auth required — new users land here)
    Route::get('/onboarding', [StudentController::class, 'onboarding'])->name('pwa.student.onboarding');
    Route::post('/onboarding', [StudentController::class, 'saveOnboarding'])->name('pwa.student.onboarding.save');

    // Migration from Telegram
    Route::get('/migrate', [AuthController::class, 'migrateFromTelegram'])->name('pwa.student.migrate');

    // Protected student routes
    Route::middleware(['auth', 'pwa.onboarding', 'pwa.lesson-lock'])->group(function () {
        Route::get('/', [StudentController::class, 'dashboard'])->name('pwa.student.dashboard');
        Route::get('/mini', [StudentController::class, 'mini'])->name('pwa.student.mini');
        Route::get('/new-tasks', [StudentController::class, 'newTasks'])->name('pwa.student.new-tasks');
        Route::get('/part2', [StudentController::class, 'part2'])->name('pwa.student.part2');
        // Подробное решение — только учителя/админы (ученикам 403 через role middleware)
        Route::get('/part2/solution/{topic}/{number}', [StudentController::class, 'part2Solution'])
            ->whereNumber('number')
            ->middleware('role:teacher,admin')
            ->name('pwa.student.part2.solution');
        Route::get('/tasks-part1', [StudentController::class, 'tasksPart1'])->name('pwa.student.tasks-part1');
        Route::post('/mini/start', [StudentController::class, 'startMini'])->name('pwa.student.mini.start');
        Route::post('/full/start', [StudentController::class, 'startFull'])->name('pwa.student.full.start');
        Route::get('/test/{attemptId}', [StudentController::class, 'test'])->name('pwa.student.test');
        Route::get('/results/{attemptId}', [StudentController::class, 'results'])->name('pwa.student.results');
        Route::get('/history', [StudentController::class, 'history'])->name('pwa.student.history');
        Route::get('/history/{studentId}/{attemptId}', [StudentController::class, 'adminHistoryDetail'])->name('pwa.student.history.student-detail');
        Route::get('/history/{historyId}', [StudentController::class, 'historyEntry'])->name('pwa.student.history.detail');
        Route::get('/profile', [StudentController::class, 'profile'])->name('pwa.student.profile');
        Route::get('/homework', [StudentController::class, 'studentHomework'])->name('pwa.student.homework');
        Route::get('/homework/{assignment}', [StudentController::class, 'showTopicHomework'])->name('pwa.student.homework.topic');
        Route::post('/homework/{assignment}/tasks/{homeworkTask}', [StudentController::class, 'submitTopicHomeworkTask'])->name('pwa.student.homework.topic.submit');

        // Lesson endpoints (для polling в dashboard и страницы урока)
        Route::get('/lessons/active',          [StudentLessonController::class, 'active'])->name('pwa.student.lessons.active');
        Route::post('/lessons/join',           [StudentLessonController::class, 'join'])->name('pwa.student.lessons.join');
        Route::post('/lessons/{id}/activity',  [StudentLessonController::class, 'activity'])->name('pwa.student.lessons.activity')->whereNumber('id');
        Route::post('/lessons/{id}/event',     [StudentLessonController::class, 'event'])->name('pwa.student.lessons.event')->whereNumber('id');
        Route::get('/lessons/{id}',            [StudentLessonController::class, 'show'])->name('pwa.student.lessons.show')->whereNumber('id');
        Route::get('/lessons/{id}/state',      [StudentLessonController::class, 'state'])->name('pwa.student.lessons.state')->whereNumber('id');
        Route::post('/lessons/{id}/answer',    [StudentLessonController::class, 'answer'])->name('pwa.student.lessons.answer')->whereNumber('id');

        Route::get('/tutor', [StudentController::class, 'tutor'])->name('pwa.student.tutor');
        Route::prefix('practice')->name('pwa.student.practice.')->group(function () {
            Route::get('/', [PracticeController::class, 'index'])->name('index');
            Route::get('/category/{slug}', [PracticeController::class, 'category'])->name('category');
            Route::get('/mini-games/{slug}', [PracticeController::class, 'showMiniGame'])->name('mini-games.show');
            Route::get('/mini-games/{slug}/leaderboard', [PracticeController::class, 'leaderboard'])->name('mini-games.leaderboard');
            Route::post('/api/mini-games/{slug}/start', [PracticeController::class, 'startRun'])->name('mini-games.start');
            Route::post('/api/mini-games/{slug}/answer', [PracticeController::class, 'answerRun'])->name('mini-games.answer');
            Route::post('/api/mini-games/{slug}/timeout', [PracticeController::class, 'timeoutRun'])->name('mini-games.timeout');
        });

        // ЕГЭ (10–11 класс)
        Route::prefix('ege-app')->name('pwa.student.ege.')->group(function () {
            Route::get('/',                   [EgeStudentController::class, 'home'])      ->name('home');
            Route::post('/full/start',        [EgeStudentController::class, 'startFull']) ->name('start');
            Route::get('/test/{attemptId}',   [EgeStudentController::class, 'test'])      ->name('test');
            Route::get('/results/{attemptId}',[EgeStudentController::class, 'results'])   ->name('results');
        });

        // ВПР (5–8 класс)
        Route::prefix('vpr')->name('pwa.student.vpr.')->group(function () {
            Route::get('/',                   [VprController::class, 'home'])         ->name('home');
            Route::get('/tasks',              [VprController::class, 'taskDatabase']) ->name('tasks');
            Route::post('/mini/start',        [VprController::class, 'startMini'])    ->name('mini.start');
            Route::post('/full/start',        [VprController::class, 'startFull'])    ->name('start');
            Route::get('/test/{attemptId}',   [VprController::class, 'test'])         ->name('test');
            Route::get('/results/{attemptId}',[VprController::class, 'results'])      ->name('results');
        });

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

    // Bug reports
    Route::post('/bug-report', [BugReportController::class, 'store'])->name('pwa.teacher.bug-report');

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
        Route::get('/homework/topic-tasks/{topicNumber}', [TeacherController::class, 'topicTasks'])->name('pwa.teacher.homework.topic-tasks')->whereNumber('topicNumber');
        Route::post('/homework/assign', [TeacherController::class, 'assignHomework'])->name('pwa.teacher.homework.assign');

        // Lessons API (lesson_session lifecycle)
        Route::post('/lessons',                              [TeacherLessonController::class, 'create'])->name('pwa.teacher.lessons.create');
        Route::post('/lessons/from-slot',                    [TeacherLessonController::class, 'fromSlot'])->name('pwa.teacher.lessons.from-slot');
        Route::get('/lessons/picker-options',                [TeacherLessonController::class, 'pickerOptions'])->name('pwa.teacher.lessons.picker-options');
        Route::get('/lessons/{id}',                          [TeacherLessonController::class, 'show'])->name('pwa.teacher.lessons.show')->whereNumber('id');
        Route::get('/lessons/{id}/state',                    [TeacherLessonController::class, 'state'])->name('pwa.teacher.lessons.state')->whereNumber('id');
        Route::get('/lessons/{id}/homework-suggestions',     [TeacherLessonController::class, 'homeworkSuggestions'])->name('pwa.teacher.lessons.homework-suggestions')->whereNumber('id');
        Route::post('/lessons/{id}/tasks',                   [TeacherLessonController::class, 'addTask'])->name('pwa.teacher.lessons.add-task')->whereNumber('id');
        Route::delete('/lessons/{id}/tasks/{taskId}',        [TeacherLessonController::class, 'removeTask'])->name('pwa.teacher.lessons.remove-task')->whereNumber('id')->whereNumber('taskId');
        Route::post('/lessons/{id}/start',                   [TeacherLessonController::class, 'start'])->name('pwa.teacher.lessons.start')->whereNumber('id');
        Route::post('/lessons/{id}/end',                     [TeacherLessonController::class, 'end'])->name('pwa.teacher.lessons.end')->whereNumber('id');
        Route::post('/lessons/{id}/participants/{studentId}/release', [TeacherLessonController::class, 'release'])->name('pwa.teacher.lessons.release')->whereNumber('id')->whereNumber('studentId');
        Route::post('/lessons/{id}/next',                    [TeacherLessonController::class, 'next'])->name('pwa.teacher.lessons.next')->whereNumber('id');
        Route::post('/lessons/{id}/note',                    [TeacherLessonController::class, 'note'])->name('pwa.teacher.lessons.note')->whereNumber('id');
        Route::post('/lessons/{id}/dont-understand',         [TeacherLessonController::class, 'dontUnderstand'])->name('pwa.teacher.lessons.dont-understand')->whereNumber('id');
        Route::post('/lessons/{id}/notes',                   [TeacherLessonController::class, 'notes'])->name('pwa.teacher.lessons.notes')->whereNumber('id');

        // Записи об ученике (student_notes) — правка/удаление без LLM
        Route::patch('/student-notes/{id}',  [StudentNoteController::class, 'update'])->name('pwa.teacher.student-notes.update')->whereNumber('id');
        Route::delete('/student-notes/{id}', [StudentNoteController::class, 'destroy'])->name('pwa.teacher.student-notes.destroy')->whereNumber('id');

        Route::get('/variants', [TeacherController::class, 'variants'])->name('pwa.teacher.variants');
        Route::get('/referrals', [TeacherController::class, 'referrals'])->name('pwa.teacher.referrals');
    });
});
