<?php
/**
 * SmartCart API - Prices
 *
 * GET /api/prices - List prices with filters
 * GET /api/prices?store={slug} - Prices by store
 * GET /api/prices?product={id} - Prices for product
 * GET /api/prices?category={slug} - Prices by category
 * POST /api/prices - Add single price
 * POST /api/prices/bulk - Bulk add prices (from extension)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET') {
    $storeSlug = $_GET['store'] ?? null;
    $productId = $_GET['product'] ?? null;
    $categorySlug = $_GET['category'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = "SELECT p.*, s.name as store_name, s.slug as store_slug
            FROM prices p
            JOIN stores s ON p.store_id = s.id
            WHERE p.is_available = 1";
    $params = [];

    if ($storeSlug) {
        $sql .= " AND s.slug = ?";
        $params[] = $storeSlug;
    }

    if ($productId) {
        $sql .= " AND p.product_id = ?";
        $params[] = $productId;
    }

    if ($categorySlug) {
        $sql .= " AND p.category_slug = ?";
        $params[] = $categorySlug;
    }

    $sql .= " ORDER BY p.parsed_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $prices = Database::query($sql, $params);

    // Get total count
    $countSql = "SELECT COUNT(*) as cnt FROM prices p JOIN stores s ON p.store_id = s.id WHERE p.is_available = 1";
    $countParams = [];

    if ($storeSlug) {
        $countSql .= " AND s.slug = ?";
        $countParams[] = $storeSlug;
    }
    if ($productId) {
        $countSql .= " AND p.product_id = ?";
        $countParams[] = $productId;
    }
    if ($categorySlug) {
        $countSql .= " AND p.category_slug = ?";
        $countParams[] = $categorySlug;
    }

    $total = Database::query($countSql, $countParams)[0]['cnt'];

    jsonResponse([
        'prices' => $prices,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} elseif ($method === 'POST') {
    $data = getJsonBody();

    if ($action === 'bulk') {
        // POST /api/prices/bulk - Bulk import from extension
        if (empty($data['store_slug']) || empty($data['products'])) {
            errorResponse('store_slug and products are required');
        }

        // Get store
        $store = Database::query("SELECT id FROM stores WHERE slug = ?", [$data['store_slug']]);
        if (empty($store)) {
            errorResponse('Store not found', 404);
        }
        $storeId = $store[0]['id'];

        $parsedAt = $data['parsed_at'] ?? date('Y-m-d H:i:s');
        $imported = 0;
        $updated = 0;

        foreach ($data['products'] as $product) {
            $name = $product['name'] ?? '';
            if (empty($name)) continue;

            $price = (float)($product['price'] ?? 0);
            if ($price <= 0) continue;

            $originalPrice = $product['originalPrice'] ?? $product['original_price'] ?? null;
            $discount = $product['discount'] ?? null;
            $weight = $product['weight'] ?? null;
            $unit = $product['unit'] ?? 'г';
            $url = $product['url'] ?? null;
            $category = $product['category'] ?? null;

            // Calculate price per kg
            $pricePerKg = calculatePricePerKg($price, $weight, $unit);

            // Try to match with existing product
            $productId = null;
            $existingProducts = Database::query("SELECT id, search_keywords FROM products");
            foreach ($existingProducts as $ep) {
                if (matchProduct($name, $ep['search_keywords'] ?? '')) {
                    $productId = $ep['id'];
                    break;
                }
            }

            // Check if price already exists (same store, similar name)
            $existing = Database::query(
                "SELECT id FROM prices WHERE store_id = ? AND store_product_name = ?",
                [$storeId, $name]
            );

            if (!empty($existing)) {
                // Update existing price
                Database::execute(
                    "UPDATE prices SET
                        price = ?, original_price = ?, discount_percent = ?,
                        weight = ?, unit = ?, price_per_kg = ?,
                        url = ?, category_slug = ?, parsed_at = ?, is_available = 1
                     WHERE id = ?",
                    [$price, $originalPrice, $discount, $weight, $unit, $pricePerKg, $url, $category, $parsedAt, $existing[0]['id']]
                );
                $updated++;
            } else {
                // Insert new price
                Database::execute(
                    "INSERT INTO prices (product_id, store_id, store_product_name, price, original_price, discount_percent, weight, unit, price_per_kg, url, category_slug, parsed_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$productId, $storeId, $name, $price, $originalPrice, $discount, $weight, $unit, $pricePerKg, $url, $category, $parsedAt]
                );
                $imported++;
            }
        }

        jsonResponse([
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'total' => count($data['products'])
        ]);

    } else {
        // POST /api/prices - Single price add
        if (empty($data['store_id']) || empty($data['name']) || empty($data['price'])) {
            errorResponse('store_id, name and price are required');
        }

        $pricePerKg = calculatePricePerKg($data['price'], $data['weight'] ?? null, $data['unit'] ?? 'г');

        Database::execute(
            "INSERT INTO prices (product_id, store_id, store_product_name, price, original_price, discount_percent, weight, unit, price_per_kg, url, category_slug)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['product_id'] ?? null,
                $data['store_id'],
                $data['name'],
                $data['price'],
                $data['original_price'] ?? null,
                $data['discount'] ?? null,
                $data['weight'] ?? null,
                $data['unit'] ?? 'г',
                $pricePerKg,
                $data['url'] ?? null,
                $data['category'] ?? null
            ]
        );

        jsonResponse([
            'success' => true,
            'id' => Database::lastInsertId()
        ], 201);
    }

} else {
    errorResponse('Method not allowed', 405);
}
