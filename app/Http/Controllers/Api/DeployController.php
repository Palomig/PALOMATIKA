<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JarvisMaterial;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Deploy webhook controller
 *
 * Provides endpoints for triggering artisan commands remotely
 * from GitHub Actions, OpenClaw bot, or other CI/CD systems.
 */
class DeployController extends Controller
{
    private const DEPLOY_REFRESH_LOCK_KEY = 'deploy:refresh:running';

    /**
     * Allowed artisan commands (whitelist for security).
     */
    private const ALLOWED_COMMANDS = [
        'deploy:refresh',
        'migrate',
        'migrate:status',
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'config:cache',
        'route:cache',
        'svg:bake',
        'svg:bake-ege',
        'pool:sync',
        'pool:flush',
        'oge:rescore-attempts',
        'oge:backfill-answers',
        'oge:abandon-stale',
        'tasks:add-status',
        'tasks:set-status',
        'task-statuses:import',
        'audit:prune',
        'materials:backfill',
        'materials:delete',
        'assets:audit-semantic-svg',
        'topics:diagnose',
        'pool:test-generate',
        'premium:gift',
        'user:promote-teacher',
        'user:promote-admin',
        'user:set-referrer',
        'user:delete-telegram',
        'qa:setup-users',
        'variants:normalize-miniapp',
        'variants:flush-miniapp',
        'variants:backfill-slots',
        'user:flush-sessions',
        'redesign:backup-puzzles',
    ];

    /**
     * Verify webhook secret. Returns error response or null if OK.
     */
    private function verifySecret(Request $request): ?JsonResponse
    {
        $secret = config('services.deploy.webhook_secret');

        if (empty($secret)) {
            Log::warning('Deploy webhook called but DEPLOY_WEBHOOK_SECRET is not configured');
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        $providedSecret = $request->header('X-Deploy-Secret');

        if (!hash_equals($secret, $providedSecret ?? '')) {
            Log::warning('Deploy webhook called with invalid secret', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return null;
    }

    /**
     * Run an artisan command and return JSON response.
     */
    private function runCommand(string $command, array $params = []): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $exitCode = Artisan::call($command, $params);
            $output = Artisan::output();

            $elapsed = round(microtime(true) - $startTime, 2);

            Log::info("Artisan '{$command}' completed in {$elapsed}s", [
                'exit_code' => $exitCode,
                'params' => $params,
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'command' => $command,
                'elapsed' => "{$elapsed}s",
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error("Artisan '{$command}' failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle deploy refresh webhook (legacy endpoint).
     *
     * POST /api/deploy/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        if (!Cache::add(self::DEPLOY_REFRESH_LOCK_KEY, true, now()->addMinutes(10))) {
            Log::warning('Deploy refresh webhook skipped because another refresh is already running', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'command' => 'deploy:refresh',
                'error' => 'Deploy refresh is already running',
            ], 409);
        }

        Log::info('Deploy refresh webhook triggered', ['ip' => $request->ip()]);

        try {
            return $this->runCommand('deploy:refresh');
        } finally {
            Cache::forget(self::DEPLOY_REFRESH_LOCK_KEY);
        }
    }

    /**
     * Run any allowed artisan command.
     *
     * POST /api/deploy/artisan
     * Header: X-Deploy-Secret: <secret>
     * Body: { "command": "migrate", "params": {"--force": true} }
     */
    public function artisan(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $command = $request->input('command');

        if (!$command || !in_array($command, self::ALLOWED_COMMANDS, true)) {
            return response()->json([
                'error' => 'Command not allowed',
                'allowed' => self::ALLOWED_COMMANDS,
            ], 400);
        }

        $params = $request->input('params', []);

        // Force --force for migrate in production
        if ($command === 'migrate' && app()->environment('production')) {
            $params['--force'] = true;
        }

        Log::info("Artisan webhook: {$command}", [
            'ip' => $request->ip(),
            'params' => $params,
        ]);

        return $this->runCommand($command, $params);
    }

    /**
     * List allowed commands.
     *
     * GET /api/deploy/commands
     */
    public function commands(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        return response()->json([
            'commands' => self::ALLOWED_COMMANDS,
        ]);
    }

    /**
     * Execute a read-only SQL query against the database.
     *
     * POST /api/deploy/query
     * Header: X-Deploy-Secret: <secret>
     * Body: { "sql": "SELECT ...", "limit": 100 }
     *
     * Only SELECT statements are allowed. Mutations are rejected.
     */
    public function query(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $sql = trim($request->input('sql', ''));
        $limit = min((int) $request->input('limit', 100), 1000);

        if ($sql === '') {
            return response()->json(['error' => 'SQL query is required'], 400);
        }

        // Only allow SELECT / SHOW / DESCRIBE / EXPLAIN
        $firstWord = strtoupper(strtok($sql, " \t\n\r"));
        $allowed = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];
        if (!in_array($firstWord, $allowed, true)) {
            return response()->json([
                'error' => 'Only read-only queries are allowed (SELECT, SHOW, DESCRIBE, EXPLAIN)',
            ], 403);
        }

        // Reject dangerous patterns
        $upper = strtoupper($sql);
        $dangerous = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'CREATE', 'GRANT', 'REVOKE'];
        foreach ($dangerous as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/', $upper)) {
                return response()->json(['error' => "Forbidden keyword: {$keyword}"], 403);
            }
        }

        try {
            $startTime = microtime(true);

            // Add LIMIT if not present in SELECT queries
            if ($firstWord === 'SELECT' && !preg_match('/\bLIMIT\b/i', $sql)) {
                $sql = rtrim($sql, '; ') . " LIMIT {$limit}";
            }

            $rows = DB::select($sql);
            $elapsed = round(microtime(true) - $startTime, 3);

            Log::info('Deploy query executed', [
                'ip' => $request->ip(),
                'sql' => mb_substr($sql, 0, 200),
                'rows' => count($rows),
                'elapsed' => "{$elapsed}s",
            ]);

            return response()->json([
                'success' => true,
                'rows' => $rows,
                'count' => count($rows),
                'elapsed' => "{$elapsed}s",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a static material HTML page directly to public/materials/.
     *
     * POST /api/deploy/material
     * Header: X-Deploy-Secret: <secret>
     * Body: { "slug": "geometricheskaia-progressiia", "html": "<!doctype html>..." }
     *
     * Returns the public URL of the created page.
     */
    public function uploadMaterial(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $slug = trim($request->input('slug', ''));
        $html = $request->input('html', '');

        if ($slug === '' || $html === '') {
            return response()->json(['error' => 'Both "slug" and "html" are required'], 400);
        }

        // Sanitize slug: only allow lowercase letters, digits, hyphens
        $slug = Str::slug($slug);
        if ($slug === '') {
            return response()->json(['error' => 'Invalid slug'], 400);
        }

        // Prevent directory traversal
        if (str_contains($slug, '/') || str_contains($slug, '..')) {
            return response()->json(['error' => 'Invalid slug'], 400);
        }

        $dir = public_path('materials');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $slug . '.html';
        $isNew = !file_exists($path);

        file_put_contents($path, $html);

        $url = url('/materials/' . $slug . '.html');

        // Upsert a JarvisMaterial DB record so the material appears in the catalog
        $title = trim($request->input('title', ''));
        $excerpt = trim($request->input('excerpt', ''));
        $category = trim($request->input('category', ''));

        if ($title === '') {
            // Extract <title> from HTML as fallback
            preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m);
            $title = isset($m[1]) ? trim($m[1]) : $slug;
        }

        $adminUser = User::where('role', 'admin')->orderBy('id')->first();

        if ($adminUser) {
            $existing = JarvisMaterial::where('slug', $slug)->first();

            if ($existing) {
                $existing->title = $title;
                if ($excerpt !== '') {
                    $existing->excerpt = $excerpt;
                }
                if ($category !== '') {
                    $existing->category = $category;
                }
                $existing->source_content = $html;
                $existing->status = JarvisMaterial::STATUS_PUBLISHED;
                if (!$existing->published_at) {
                    $existing->published_at = now();
                }
                $existing->save();
            } else {
                JarvisMaterial::create([
                    'owner_teacher_id' => $adminUser->id,
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt ?: null,
                    'category' => $category ?: null,
                    'source_content' => $html,
                    'status' => JarvisMaterial::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]);
            }
        }

        Log::info('Material uploaded via deploy API', [
            'slug' => $slug,
            'ip' => $request->ip(),
            'size' => strlen($html),
            'new' => $isNew,
        ]);

        return response()->json([
            'success' => true,
            'slug' => $slug,
            'url' => $url,
            'new' => $isNew,
            'size' => strlen($html),
        ], $isNew ? 201 : 200);
    }

    /**
     * List static material HTML files in public/materials/.
     *
     * GET /api/deploy/materials
     * Header: X-Deploy-Secret: <secret>
     */
    public function listMaterials(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $dir = public_path('materials');
        $files = [];

        if (is_dir($dir)) {
            foreach (glob($dir . '/*.html') as $file) {
                $name = basename($file, '.html');
                $files[] = [
                    'slug' => $name,
                    'url' => url('/materials/' . $name . '.html'),
                    'size' => filesize($file),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'count' => count($files),
            'materials' => $files,
        ]);
    }

    /**
     * List database tables.
     *
     * GET /api/deploy/tables
     * Header: X-Deploy-Secret: <secret>
     */
    public function tables(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database');
            $key = "Tables_in_{$dbName}";

            $result = [];
            foreach ($tables as $table) {
                $name = $table->$key ?? array_values((array) $table)[0];
                $count = DB::selectOne("SELECT COUNT(*) as cnt FROM `{$name}`");
                $result[] = [
                    'table' => $name,
                    'rows' => $count->cnt ?? 0,
                ];
            }

            return response()->json(['tables' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a QA session for a test user by email.
     * Returns the session ID to use as a cookie for browser automation.
     *
     * POST /api/deploy/qa-session
     * Header: X-Deploy-Secret: <secret>
     * Body: { "email": "qa-student@palomatika.ru" }
     */
    public function qaSession(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $email = trim($request->input('email', 'qa-student@palomatika.ru'));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => "User not found: {$email}"], 404);
        }

        // Generate a session ID
        $sessionId = Str::random(40);

        // Build Laravel session payload (serialized PHP array)
        $guardKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard' . 'web' . 'App\Models\User');
        $payload = serialize([
            '_token' => Str::random(40),
            $guardKey => $user->id,
            '_previous' => ['url' => 'https://student.palomatika.ru/'],
            '_flash' => ['old' => [], 'new' => []],
        ]);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => 'QA-Playwright/1.0',
            'payload' => base64_encode($payload),
            'last_activity' => time(),
        ]);

        // Encrypt session ID as Laravel does for the cookie value
        $encryptedSessionId = Crypt::encrypt($sessionId, false);

        Log::info("QA session created for user {$user->id} ({$email})", ['session' => $sessionId]);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'cookie_value' => $encryptedSessionId,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'cookie_name' => config('session.cookie', 'laravel_session'),
        ]);
    }

    /**
     * Tail the Laravel log file (last N lines).
     *
     * GET /api/deploy/log?lines=50
     * Header: X-Deploy-Secret: <secret>
     */
    public function tailLog(Request $request): JsonResponse
    {
        if ($error = $this->verifySecret($request)) {
            return $error;
        }

        $lines = min((int) $request->input('lines', 50), 200);
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['lines' => [], 'note' => 'Log file not found']);
        }

        $content = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tail = array_slice($content, -$lines);

        return response()->json(['lines' => $tail, 'total' => count($content)]);
    }
}
