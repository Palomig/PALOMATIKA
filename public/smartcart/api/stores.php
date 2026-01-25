<?php
/**
 * SmartCart API - Stores
 *
 * GET /api/stores - List all stores
 * GET /api/stores/{slug} - Get store by slug
 * GET /api/stores/{slug}/categories - Get categories with URLs for parsing
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$slug = $_GET['slug'] ?? null;
$action = $_GET['action'] ?? null;

if ($method === 'GET') {
    if ($slug && $action === 'categories') {
        // GET /api/stores/{slug}/categories
        $store = Database::query("SELECT * FROM stores WHERE slug = ?", [$slug]);

        if (empty($store)) {
            errorResponse('Store not found', 404);
        }

        $categories = Database::query("SELECT * FROM categories ORDER BY sort_order");

        $result = array_map(function($cat) use ($slug) {
            return [
                'slug' => $cat['slug'],
                'name' => $cat['name'],
                'emoji' => $cat['emoji'],
                'url' => "https://market-delivery.yandex.ru/retail/{$slug}/category/{$cat['url_path']}"
            ];
        }, $categories);

        jsonResponse([
            'store' => $store[0],
            'categories' => $result
        ]);

    } elseif ($slug) {
        // GET /api/stores/{slug}
        $store = Database::query("SELECT * FROM stores WHERE slug = ?", [$slug]);

        if (empty($store)) {
            errorResponse('Store not found', 404);
        }

        // Get price stats for this store
        $stats = Database::query(
            "SELECT COUNT(*) as products, MAX(parsed_at) as last_parsed FROM prices WHERE store_id = ?",
            [$store[0]['id']]
        );

        jsonResponse([
            'store' => $store[0],
            'stats' => [
                'products' => $stats[0]['products'],
                'last_parsed' => $stats[0]['last_parsed'] ? relativeTime($stats[0]['last_parsed']) : null
            ]
        ]);

    } else {
        // GET /api/stores - List all stores
        $stores = Database::query("SELECT * FROM stores WHERE is_active = 1 ORDER BY name");

        // Add stats for each store
        foreach ($stores as &$store) {
            $stats = Database::query(
                "SELECT COUNT(*) as products, MAX(parsed_at) as last_parsed FROM prices WHERE store_id = ?",
                [$store['id']]
            );
            $store['products_count'] = $stats[0]['products'];
            $store['last_parsed'] = $stats[0]['last_parsed'] ? relativeTime($stats[0]['last_parsed']) : null;
        }

        jsonResponse(['stores' => $stores]);
    }
} else {
    errorResponse('Method not allowed', 405);
}
