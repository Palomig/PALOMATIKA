<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        'oge:rescore-attempts',
        'oge:backfill-answers',
        'tasks:add-status',
        'tasks:set-status',
        'task-statuses:import',
        'audit:prune',
        'assets:audit-semantic-svg',
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
}
