<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\JarvisMaterialController;
use App\Http\Controllers\Api\ScheduleController;
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
Route::post('/deploy/qa-session', [DeployController::class, 'qaSession']);
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


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
});
