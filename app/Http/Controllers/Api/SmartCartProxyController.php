<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxy controller for SmartCart API
 * Forwards requests from /api/prices/* to /smartcart/api/prices.php
 */
class SmartCartProxyController extends Controller
{
    /**
     * Handle CORS preflight
     */
    private function corsHeaders()
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Access-Control-Max-Age' => '86400',
        ];
    }

    /**
     * POST /api/prices/bulk - Proxy to SmartCart
     */
    public function bulkPrices(Request $request)
    {
        // Include SmartCart API directly
        $smartcartPath = public_path('smartcart/api/prices.php');

        if (!file_exists($smartcartPath)) {
            return response()->json([
                'success' => false,
                'error' => 'SmartCart API not found'
            ], 404)->withHeaders($this->corsHeaders());
        }

        // Set action for the SmartCart API
        $_GET['action'] = 'bulk';

        // Capture output
        ob_start();

        try {
            require $smartcartPath;
        } catch (\Throwable $e) {
            ob_end_clean();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500)->withHeaders($this->corsHeaders());
        }

        $output = ob_get_clean();

        // Return response with CORS headers
        return response($output, 200)
            ->header('Content-Type', 'application/json')
            ->withHeaders($this->corsHeaders());
    }

    /**
     * GET /api/stores - Proxy to SmartCart
     */
    public function stores(Request $request)
    {
        $smartcartPath = public_path('smartcart/api/stores.php');

        if (!file_exists($smartcartPath)) {
            return response()->json([
                'success' => false,
                'error' => 'SmartCart API not found'
            ], 404)->withHeaders($this->corsHeaders());
        }

        ob_start();

        try {
            require $smartcartPath;
        } catch (\Throwable $e) {
            ob_end_clean();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500)->withHeaders($this->corsHeaders());
        }

        $output = ob_get_clean();

        return response($output, 200)
            ->header('Content-Type', 'application/json')
            ->withHeaders($this->corsHeaders());
    }

    /**
     * GET /api/cart - Proxy to SmartCart
     */
    public function cart(Request $request)
    {
        $smartcartPath = public_path('smartcart/api/cart.php');

        if (!file_exists($smartcartPath)) {
            return response()->json([
                'success' => false,
                'error' => 'SmartCart API not found'
            ], 404)->withHeaders($this->corsHeaders());
        }

        ob_start();

        try {
            require $smartcartPath;
        } catch (\Throwable $e) {
            ob_end_clean();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500)->withHeaders($this->corsHeaders());
        }

        $output = ob_get_clean();

        return response($output, 200)
            ->header('Content-Type', 'application/json')
            ->withHeaders($this->corsHeaders());
    }

    /**
     * Handle OPTIONS preflight request
     */
    public function options()
    {
        return response('', 204)->withHeaders($this->corsHeaders());
    }
}
