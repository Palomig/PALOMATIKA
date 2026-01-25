<?php
/**
 * SmartCart API - Products
 *
 * GET /api/products - List all base products
 * GET /api/products/{id} - Get product with prices
 * POST /api/products - Create product
 * PUT /api/products/{id} - Update product
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$categorySlug = $_GET['category'] ?? null;

if ($method === 'GET') {
    if ($id) {
        // GET /api/products/{id}
        $product = Database::query(
            "SELECT p.*, c.name as category_name, c.emoji as category_emoji
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = ?",
            [$id]
        );

        if (empty($product)) {
            errorResponse('Product not found', 404);
        }

        // Get prices for this product
        $prices = Database::query(
            "SELECT pr.*, s.name as store_name, s.slug as store_slug
             FROM prices pr
             JOIN stores s ON pr.store_id = s.id
             WHERE pr.product_id = ? AND pr.is_available = 1
             ORDER BY pr.price ASC",
            [$id]
        );

        jsonResponse([
            'product' => $product[0],
            'prices' => $prices,
            'best_price' => $prices[0] ?? null
        ]);

    } else {
        // GET /api/products
        $sql = "SELECT p.*, c.name as category_name, c.emoji as category_emoji, c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id";
        $params = [];

        if ($categorySlug) {
            $sql .= " WHERE c.slug = ?";
            $params[] = $categorySlug;
        }

        $sql .= " ORDER BY c.sort_order, p.name";

        $products = Database::query($sql, $params);

        // Add best price for each product
        foreach ($products as &$product) {
            $bestPrice = Database::query(
                "SELECT price, s.name as store_name
                 FROM prices pr
                 JOIN stores s ON pr.store_id = s.id
                 WHERE pr.product_id = ? AND pr.is_available = 1
                 ORDER BY pr.price ASC LIMIT 1",
                [$product['id']]
            );
            $product['best_price'] = $bestPrice[0]['price'] ?? null;
            $product['best_store'] = $bestPrice[0]['store_name'] ?? null;
        }

        jsonResponse(['products' => $products]);
    }

} elseif ($method === 'POST') {
    $data = getJsonBody();

    if (empty($data['name'])) {
        errorResponse('name is required');
    }

    // Get category id
    $categoryId = null;
    if (!empty($data['category_slug'])) {
        $cat = Database::query("SELECT id FROM categories WHERE slug = ?", [$data['category_slug']]);
        $categoryId = $cat[0]['id'] ?? null;
    } elseif (!empty($data['category_id'])) {
        $categoryId = $data['category_id'];
    }

    Database::execute(
        "INSERT INTO products (name, category_id, search_keywords, default_weight, default_unit)
         VALUES (?, ?, ?, ?, ?)",
        [
            $data['name'],
            $categoryId,
            $data['search_keywords'] ?? $data['name'],
            $data['default_weight'] ?? null,
            $data['default_unit'] ?? 'г'
        ]
    );

    jsonResponse([
        'success' => true,
        'id' => Database::lastInsertId()
    ], 201);

} elseif ($method === 'PUT') {
    if (!$id) {
        errorResponse('Product ID is required');
    }

    $data = getJsonBody();

    $fields = [];
    $params = [];

    if (isset($data['name'])) {
        $fields[] = "name = ?";
        $params[] = $data['name'];
    }
    if (isset($data['category_id'])) {
        $fields[] = "category_id = ?";
        $params[] = $data['category_id'];
    }
    if (isset($data['search_keywords'])) {
        $fields[] = "search_keywords = ?";
        $params[] = $data['search_keywords'];
    }
    if (isset($data['default_weight'])) {
        $fields[] = "default_weight = ?";
        $params[] = $data['default_weight'];
    }
    if (isset($data['default_unit'])) {
        $fields[] = "default_unit = ?";
        $params[] = $data['default_unit'];
    }

    if (empty($fields)) {
        errorResponse('No fields to update');
    }

    $params[] = $id;
    Database::execute(
        "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?",
        $params
    );

    jsonResponse(['success' => true]);

} else {
    errorResponse('Method not allowed', 405);
}
