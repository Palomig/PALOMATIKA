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
 * Provides an endpoint for triggering post-deploy refresh
 * from GitHub Actions or other CI/CD systems.
 */
class DeployController extends Controller
{
    /**
     * Handle deploy refresh webhook
     *
     * POST /api/deploy/refresh
     * Header: X-Deploy-Secret: <secret>
     */
    public function refresh(Request $request): JsonResponse
    {
        // Verify webhook secret
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

        Log::info('Deploy refresh webhook triggered', [
            'ip' => $request->ip(),
        ]);

        try {
            $startTime = microtime(true);

            // Run deploy:refresh command
            $exitCode = Artisan::call('deploy:refresh');
            $output = Artisan::output();

            $elapsed = round(microtime(true) - $startTime, 2);

            Log::info("Deploy refresh completed in {$elapsed}s", [
                'exit_code' => $exitCode,
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Deploy refresh completed',
                'elapsed' => "{$elapsed}s",
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Deploy refresh failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
