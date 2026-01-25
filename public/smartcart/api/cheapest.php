<?php
/**
 * SmartCart API - Find cheapest products
 *
 * GET /api/cheapest.php?category=milk - Cheapest in category
 * GET /api/cheapest.php?category=milk&limit=10 - With limit
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$category = $_GET['category'] ?? null;
$limit = min((int)($_GET['limit'] ?? 20), 100);

if (empty($category)) {
    errorResponse('Category parameter is required');
}

$results = Database::query(
    "SELECT
        p.store_product_name as name,
        p.price,
        p.weight,
        p.unit,
        p.price_per_kg,
        s.slug as store,
        s.name as store_name
     FROM prices p
     JOIN stores s ON p.store_id = s.id
     WHERE p.is_available = 1
       AND p.category_slug = ?
     ORDER BY COALESCE(p.price_per_kg, p.price) ASC
     LIMIT ?",
    [$category, $limit]
);

jsonResponse([
    'category' => $category,
    'count' => count($results),
    'products' => $results
]);
