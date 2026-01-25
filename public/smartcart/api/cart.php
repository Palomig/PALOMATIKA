<?php
/**
 * SmartCart API - Shopping Cart
 *
 * GET /api/cart - Get current shopping list
 * POST /api/cart/add - Add item to cart
 * POST /api/cart/remove - Remove item from cart
 * POST /api/cart/clear - Clear cart
 * POST /api/cart/toggle - Toggle item checked status
 * GET /api/cart/compare - Compare cart prices across stores
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET') {
    if ($action === 'compare') {
        // GET /api/cart/compare - Compare prices across stores
        $items = Database::query("SELECT * FROM shopping_list WHERE is_checked = 0");

        if (empty($items)) {
            jsonResponse(['comparison' => [], 'message' => 'Cart is empty']);
        }

        $comparison = compareStoresForCart($items);

        jsonResponse([
            'items_count' => count($items),
            'comparison' => $comparison
        ]);

    } else {
        // GET /api/cart - Get shopping list
        $items = Database::query(
            "SELECT sl.*, p.name as product_name_linked
             FROM shopping_list sl
             LEFT JOIN products p ON sl.product_id = p.id
             ORDER BY sl.is_checked ASC, sl.added_at DESC"
        );

        // Format for extension
        $formatted = array_map(function($item) {
            return [
                'id' => $item['id'],
                'name' => $item['product_name'],
                'search_term' => $item['search_term'] ?? $item['product_name'],
                'quantity' => $item['quantity'],
                'expected_price' => $item['expected_price'],
                'url' => $item['url'],
                'is_checked' => (bool)$item['is_checked'],
                'added_at' => $item['added_at']
            ];
        }, $items);

        jsonResponse([
            'items' => $formatted,
            'total_count' => count($items),
            'checked_count' => count(array_filter($items, fn($i) => $i['is_checked']))
        ]);
    }

} elseif ($method === 'POST') {
    $data = getJsonBody();

    if ($action === 'add') {
        // POST /api/cart/add
        if (empty($data['name'])) {
            errorResponse('name is required');
        }

        Database::execute(
            "INSERT INTO shopping_list (product_id, product_name, search_term, quantity, expected_price, url)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['product_id'] ?? null,
                $data['name'],
                $data['search_term'] ?? $data['name'],
                $data['quantity'] ?? 1,
                $data['expected_price'] ?? null,
                $data['url'] ?? null
            ]
        );

        jsonResponse([
            'success' => true,
            'id' => Database::lastInsertId()
        ], 201);

    } elseif ($action === 'remove') {
        // POST /api/cart/remove
        if (empty($data['id'])) {
            errorResponse('id is required');
        }

        Database::execute("DELETE FROM shopping_list WHERE id = ?", [$data['id']]);

        jsonResponse(['success' => true]);

    } elseif ($action === 'toggle') {
        // POST /api/cart/toggle
        if (empty($data['id'])) {
            errorResponse('id is required');
        }

        Database::execute(
            "UPDATE shopping_list SET is_checked = NOT is_checked WHERE id = ?",
            [$data['id']]
        );

        jsonResponse(['success' => true]);

    } elseif ($action === 'clear') {
        // POST /api/cart/clear
        $onlyChecked = $data['only_checked'] ?? false;

        if ($onlyChecked) {
            Database::execute("DELETE FROM shopping_list WHERE is_checked = 1");
        } else {
            Database::execute("DELETE FROM shopping_list");
        }

        jsonResponse(['success' => true]);

    } elseif ($action === 'bulk') {
        // POST /api/cart/bulk - Add multiple items
        if (empty($data['items']) || !is_array($data['items'])) {
            errorResponse('items array is required');
        }

        $added = 0;
        foreach ($data['items'] as $item) {
            if (empty($item['name'])) continue;

            Database::execute(
                "INSERT INTO shopping_list (product_id, product_name, search_term, quantity, expected_price, url)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $item['product_id'] ?? null,
                    $item['name'],
                    $item['search_term'] ?? $item['name'],
                    $item['quantity'] ?? 1,
                    $item['expected_price'] ?? null,
                    $item['url'] ?? null
                ]
            );
            $added++;
        }

        jsonResponse([
            'success' => true,
            'added' => $added
        ], 201);

    } else {
        errorResponse('Unknown action');
    }

} else {
    errorResponse('Method not allowed', 405);
}
