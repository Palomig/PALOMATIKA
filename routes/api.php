<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\DuelController;
use App\Http\Controllers\Api\HomeworkController;
use App\Http\Controllers\Api\JarvisMaterialController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Auth\TelegramBotAuthController;
use App\Http\Controllers\GeometryEditorController;
use App\Http\Controllers\TestPdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Telegram Bot Auth (deep link flow)
Route::post('/telegram/generate-token', [TelegramBotAuthController::class, 'generateToken']);
Route::get('/telegram/check-token/{token}', [TelegramBotAuthController::class, 'checkToken']);
Route::get('/telegram/session-check', [TelegramBotAuthController::class, 'sessionCheck'])->middleware('web');
Route::post('/telegram/diag', [TelegramBotAuthController::class, 'diag']);

// OAuth
Route::get('/oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
Route::post('/oauth/{provider}/callback', [OAuthController::class, 'callback']);
Route::post('/referral/track', [OAuthController::class, 'trackReferral']);

// Deploy webhooks (called by GitHub Actions / OpenClaw bot)
Route::post('/deploy/refresh', [DeployController::class, 'refresh']);
Route::post('/deploy/artisan', [DeployController::class, 'artisan']);
Route::get('/deploy/commands', [DeployController::class, 'commands']);
Route::post('/deploy/query', [DeployController::class, 'query']);
Route::get('/deploy/tables', [DeployController::class, 'tables']);
Route::get('/deploy/log', [DeployController::class, 'tailLog']);
Route::post('/deploy/material', [DeployController::class, 'uploadMaterial']);
Route::get('/deploy/materials', [DeployController::class, 'listMaterials']);

// Schedule API (protected by X-Deploy-Secret)
Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/schedule/today', [ScheduleController::class, 'today']);
Route::post('/schedule', [ScheduleController::class, 'store']);
Route::put('/schedule/{id}', [ScheduleController::class, 'update']);
Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy']);
Route::get('/students/search', [ScheduleController::class, 'searchStudents']);

// Geometry Editor API
Route::middleware(['auth', 'role:admin'])->prefix('geometry')->group(function () {
    Route::get('/stats', [GeometryEditorController::class, 'stats']);
    Route::get('/{taskId}/load', [GeometryEditorController::class, 'load']);
    Route::post('/{taskId}/save', [GeometryEditorController::class, 'save']);
    Route::delete('/{taskId}/metadata', [GeometryEditorController::class, 'delete']);
});


Route::middleware(['auth:sanctum', 'role:teacher,admin'])->group(function () {
    Route::get('/topics', [TopicController::class, 'index']);
    Route::get('/topics/{topic}', [TopicController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:teacher,admin', 'throttle:oge-variants-v1'])->group(function () {
    Route::post('/oge/variants/generator', [TestPdfController::class, 'apiCreateOgeGeneratorVariantV1']);
    Route::post('/oge/variants/custom-random', [TestPdfController::class, 'apiCreateOgeCustomRandomVariantV1']);
    Route::get('/oge/variants/{hash}', [TestPdfController::class, 'apiGetOgeVariantV1'])
        ->where('hash', '[a-z0-9]{5,16}');
});

Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('materials')->group(function () {
    Route::get('/', [JarvisMaterialController::class, 'index']);
    Route::post('/', [JarvisMaterialController::class, 'store']);
    Route::get('/{material}', [JarvisMaterialController::class, 'show']);
    Route::patch('/{material}', [JarvisMaterialController::class, 'update']);
    Route::post('/{material}/publish', [JarvisMaterialController::class, 'publish']);
    Route::post('/{material}/archive', [JarvisMaterialController::class, 'archive']);
});

Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/by-category', [SkillController::class, 'byCategory']);
Route::get('/skills/{skill}', [SkillController::class, 'show']);

Route::get('/badges', [BadgeController::class, 'index']);

Route::get('/leaderboard/all-time', [LeaderboardController::class, 'allTime']);
Route::get('/leaderboard/leagues', [LeaderboardController::class, 'leagues']);

// Tasks (public for practice, auth optional for tracking)
Route::get('/tasks/next', [TaskController::class, 'getNext']);
Route::get('/tasks/{task}', [TaskController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Topics progress
    Route::get('/topics/{topic}/progress', [TopicController::class, 'progress']);

    // Tasks (protected actions)
    Route::post('/tasks/{task}/start', [TaskController::class, 'startAttempt']);
    Route::post('/attempts/{attempt}/submit', [TaskController::class, 'submitAttempt']);

    // Skills progress
    Route::get('/skills/progress', [SkillController::class, 'userProgress']);

    // User progress
    Route::get('/progress/dashboard', [ProgressController::class, 'dashboard']);
    Route::get('/progress/history', [ProgressController::class, 'history']);
    Route::get('/progress/topics', [ProgressController::class, 'topicProgress']);
    Route::post('/progress/streak', [ProgressController::class, 'updateStreak']);

    // Leaderboard
    Route::get('/leaderboard/weekly', [LeaderboardController::class, 'weekly']);

    // Badges
    Route::get('/badges/user', [BadgeController::class, 'userBadges']);
    Route::post('/badges/{userBadge}/toggle-showcase', [BadgeController::class, 'toggleShowcase']);
    Route::post('/badges/check', [BadgeController::class, 'checkAndAward']);

    // Homework
    Route::get('/homework', [HomeworkController::class, 'index']);
    Route::post('/homework', [HomeworkController::class, 'store']);
    Route::get('/homework/{homework}', [HomeworkController::class, 'show']);
    Route::get('/homework/{homework}/statistics', [HomeworkController::class, 'statistics']);
    Route::post('/homework/assignments/{assignment}/start', [HomeworkController::class, 'start']);
    Route::post('/homework/assignments/{assignment}/complete', [HomeworkController::class, 'complete']);

    // Duels
    Route::get('/duels', [DuelController::class, 'index']);
    Route::post('/duels', [DuelController::class, 'create']);
    Route::get('/duels/{duel}', [DuelController::class, 'show']);
    Route::post('/duels/{duel}/accept', [DuelController::class, 'accept']);
    Route::post('/duels/{duel}/decline', [DuelController::class, 'decline']);
    Route::post('/duels/{duel}/results', [DuelController::class, 'submitResults']);
});
