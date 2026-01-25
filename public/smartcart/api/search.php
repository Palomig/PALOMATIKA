<?php
/**
 * SmartCart API - Search products
 *
 * GET /api/search.php?q=молоко - Search for products
 * GET /api/search.php?q=молоко&store=perekrestok - Search in specific store
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$query = $_GET['q'] ?? '';
$storeSlug = $_GET['store'] ?? null;
$limit = min((int)($_GET['limit'] ?? 50), 200);

if (empty($query)) {
    errorResponse('Query parameter "q" is required');
}

$searchTerm = '%' . $query . '%';

$sql = "SELECT
            p.store_product_name as name,
            p.price,
            p.original_price,
            p.discount_percent as discount,
            p.weight,
            p.unit,
            p.category_slug as category,
            s.slug as store,
            s.name as store_name
        FROM prices p
        JOIN stores s ON p.store_id = s.id
        WHERE p.is_available = 1
          AND p.store_product_name LIKE ?";

$params = [$searchTerm];

if ($storeSlug) {
    $sql .= " AND s.slug = ?";
    $params[] = $storeSlug;
}

$sql .= " ORDER BY p.price ASC LIMIT ?";
$params[] = $limit;

$results = Database::query($sql, $params);

jsonResponse([
    'query' => $query,
    'count' => count($results),
    'results' => $results
]);
