<?php
/**
 * SmartCart API - Statistics
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$stats = [
    'products' => Database::query("SELECT COUNT(*) as cnt FROM products")[0]['cnt'] ?? 0,
    'prices' => Database::query("SELECT COUNT(*) as cnt FROM prices WHERE is_available = 1")[0]['cnt'] ?? 0,
    'recipes' => Database::query("SELECT COUNT(*) as cnt FROM recipes")[0]['cnt'] ?? 0,
    'stores' => Database::query("SELECT COUNT(*) as cnt FROM stores WHERE is_active = 1")[0]['cnt'] ?? 0,
    'categories' => Database::query("SELECT COUNT(DISTINCT category_slug) as cnt FROM prices WHERE category_slug IS NOT NULL")[0]['cnt'] ?? 0,
];

// Per-store stats
$storeStats = Database::query(
    "SELECT s.slug, s.name, COUNT(p.id) as price_count
     FROM stores s
     LEFT JOIN prices p ON s.id = p.store_id AND p.is_available = 1
     WHERE s.is_active = 1
     GROUP BY s.id
     ORDER BY price_count DESC"
);

$stats['stores_detail'] = $storeStats;

jsonResponse($stats);
