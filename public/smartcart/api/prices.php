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

// Special CORS for Yandex Market Delivery
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://market-delivery.yandex.ru',
    'chrome-extension://',
];

$corsAllowed = false;
foreach ($allowedOrigins as $allowed) {
    if (str_starts_with($origin, $allowed) || $origin === $allowed) {
        $corsAllowed = true;
        break;
    }
}

// Allow localhost for development
if (str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1')) {
    $corsAllowed = true;
}

if ($corsAllowed && $origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
        // Accept both 'store' and 'store_slug' for compatibility
        $storeSlug = $data['store'] ?? $data['store_slug'] ?? null;
        $category = $data['category'] ?? null;
        $products = $data['products'] ?? [];

        // Validate required fields
        if (empty($storeSlug)) {
            jsonResponse([
                'success' => false,
                'error' => 'store is required'
            ], 400);
        }

        if (empty($category)) {
            jsonResponse([
                'success' => false,
                'error' => 'category is required'
            ], 400);
        }

        if (!is_array($products) || empty($products)) {
            jsonResponse([
                'success' => false,
                'error' => 'products array is required'
            ], 400);
        }

        // Get or create store
        $store = Database::query("SELECT id FROM stores WHERE slug = ?", [$storeSlug]);

        if (empty($store)) {
            // Auto-create store
            $storeName = ucfirst(str_replace(['_', '-'], ' ', $storeSlug));
            Database::execute(
                "INSERT INTO stores (slug, name, delivery_time_min, delivery_time_max, min_order, is_active) VALUES (?, ?, 30, 60, 0, 1)",
                [$storeSlug, $storeName]
            );
            $storeId = Database::lastInsertId();
        } else {
            $storeId = $store[0]['id'];
        }

        // Get exportedAt or use current time
        $exportedAt = $data['exportedAt'] ?? $data['parsed_at'] ?? date('Y-m-d H:i:s');
        if (str_contains($exportedAt, 'T')) {
            $exportedAt = date('Y-m-d H:i:s', strtotime($exportedAt));
        }

        $count = 0;
        $errors = [];

        foreach ($products as $index => $product) {
            try {
                $name = trim($product['name'] ?? '');
                if (empty($name)) {
                    $errors[] = "Product at index {$index} has no name";
                    continue;
                }

                $price = (int)($product['price'] ?? 0);
                if ($price <= 0) {
                    $errors[] = "Product '{$name}' has invalid price";
                    continue;
                }

                $originalPrice = isset($product['originalPrice']) && $product['originalPrice'] > 0
                    ? (int)$product['originalPrice']
                    : null;

                $discount = isset($product['discount']) && $product['discount'] > 0
                    ? (int)$product['discount']
                    : null;

                $weight = isset($product['weight']) ? (float)$product['weight'] : null;
                $unit = $product['unit'] ?? 'г';
                $pricePerKg = isset($product['pricePerKg']) ? (int)$product['pricePerKg'] : calculatePricePerKg($price, $weight, $unit);
                $url = $product['url'] ?? null;

                // Individual parsedAt or use exportedAt
                $parsedAt = $product['parsedAt'] ?? $exportedAt;
                if (str_contains($parsedAt, 'T')) {
                    $parsedAt = date('Y-m-d H:i:s', strtotime($parsedAt));
                }

                // Check if price already exists (same store + same name)
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
                } else {
                    // Try to match with existing product
                    $productId = null;
                    $existingProducts = Database::query("SELECT id, search_keywords FROM products");
                    foreach ($existingProducts as $ep) {
                        if (!empty($ep['search_keywords']) && matchProduct($name, $ep['search_keywords'])) {
                            $productId = $ep['id'];
                            break;
                        }
                    }

                    // Insert new price
                    Database::execute(
                        "INSERT INTO prices (product_id, store_id, store_product_name, price, original_price, discount_percent, weight, unit, price_per_kg, url, category_slug, parsed_at, is_available)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                        [$productId, $storeId, $name, $price, $originalPrice, $discount, $weight, $unit, $pricePerKg, $url, $category, $parsedAt]
                    );
                }

                $count++;

            } catch (Exception $e) {
                $errors[] = "Error processing product '{$name}': " . $e->getMessage();
            }
        }

        $response = [
            'success' => true,
            'message' => "Сохранено {$count} товаров",
            'count' => $count
        ];

        if (!empty($errors) && count($errors) <= 5) {
            $response['warnings'] = $errors;
        }

        jsonResponse($response);

    } else {
        // POST /api/prices - Single price add
        if (empty($data['store_id']) || empty($data['name']) || empty($data['price'])) {
            jsonResponse([
                'success' => false,
                'error' => 'store_id, name and price are required'
            ], 400);
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
    jsonResponse([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}
