<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deploy webhook controller
 *
 * Provides endpoints for triggering artisan commands remotely
 * from GitHub Actions, OpenClaw bot, or other CI/CD systems.
 */
class DeployController extends Controller
{
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
        'tasks:add-status',
        'tasks:set-status',
        'task-statuses:import',
        'audit:prune',
        'assets:audit-semantic-svg',
        'topics:diagnose',
        'pool:test-generate',
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

        Log::info('Deploy refresh webhook triggered', ['ip' => $request->ip()]);

        return $this->runCommand('deploy:refresh');
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
}
