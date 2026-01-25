<?php
/**
 * SmartCart API - Categories
 *
 * GET /api/categories.php - Get all categories
 * GET /api/categories.php?store=perekrestok - Get categories with counts for specific store
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$storeSlug = $_GET['store'] ?? null;

if ($method === 'GET') {
    if ($storeSlug) {
        // Get categories with counts for specific store
        $store = Database::query("SELECT id FROM stores WHERE slug = ?", [$storeSlug]);

        if (empty($store)) {
            errorResponse('Store not found', 404);
        }

        $storeId = $store[0]['id'];

        // Get categories with product counts
        $categories = Database::query(
            "SELECT
                p.category_slug as slug,
                COALESCE(c.name, p.category_slug) as name,
                COUNT(*) as count
             FROM prices p
             LEFT JOIN categories c ON p.category_slug = c.slug
             WHERE p.store_id = ? AND p.is_available = 1 AND p.category_slug IS NOT NULL
             GROUP BY p.category_slug
             ORDER BY count DESC",
            [$storeId]
        );

        jsonResponse([
            'store' => $storeSlug,
            'categories' => $categories,
            'total' => array_sum(array_column($categories, 'count'))
        ]);
    } else {
        // Get all base categories
        $categories = Database::query(
            "SELECT slug, name, emoji, url_path, sort_order
             FROM categories
             ORDER BY sort_order"
        );

        jsonResponse(['categories' => $categories]);
    }
} else {
    errorResponse('Method not allowed', 405);
}
