<?php
/**
 * SmartCart API - Cleanup (delete non-food items)
 *
 * GET /api/cleanup.php?pattern=кошек&dry_run=1 - Preview what will be deleted
 * POST /api/cleanup.php?pattern=кошек - Actually delete
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$pattern = $_GET['pattern'] ?? null;
$dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === '1';

if (empty($pattern)) {
    errorResponse('Pattern parameter is required');
}

$searchTerm = '%' . $pattern . '%';

if ($method === 'GET' || $dryRun) {
    // Preview mode - show what would be deleted
    $items = Database::query(
        "SELECT id, store_product_name as name, price, category_slug as category
         FROM prices
         WHERE store_product_name LIKE ?
         ORDER BY store_product_name
         LIMIT 100",
        [$searchTerm]
    );

    $total = Database::query(
        "SELECT COUNT(*) as cnt FROM prices WHERE store_product_name LIKE ?",
        [$searchTerm]
    )[0]['cnt'];

    jsonResponse([
        'pattern' => $pattern,
        'total_matches' => (int)$total,
        'preview' => $items,
        'message' => "Found {$total} items matching '{$pattern}'. Use POST to delete."
    ]);

} elseif ($method === 'POST') {
    // Delete mode
    $count = Database::query(
        "SELECT COUNT(*) as cnt FROM prices WHERE store_product_name LIKE ?",
        [$searchTerm]
    )[0]['cnt'];

    Database::execute(
        "DELETE FROM prices WHERE store_product_name LIKE ?",
        [$searchTerm]
    );

    jsonResponse([
        'success' => true,
        'pattern' => $pattern,
        'deleted' => (int)$count
    ]);

} else {
    errorResponse('Method not allowed', 405);
}
