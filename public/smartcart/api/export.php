<?php
/**
 * SmartCart API - Export/Import
 *
 * GET /api/export.php?type=all - Export all data
 * GET /api/export.php?type=recipes - Export only recipes
 * GET /api/export.php?type=prices - Export prices
 * GET /api/export.php?type=prices&store=perekrestok - Export prices for specific store
 * POST /api/export.php - Import data
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'all';
$storeFilter = $_GET['store'] ?? null;
$categoryFilter = $_GET['categories'] ?? null; // Comma-separated list of categories
$categoriesArray = $categoryFilter ? array_map('trim', explode(',', $categoryFilter)) : null;

if ($method === 'GET') {
    $data = [
        'exported_at' => date('c'),
        'version' => APP_VERSION
    ];

    if ($type === 'all' || $type === 'recipes') {
        // Export recipes with ingredients
        $recipes = Database::query("SELECT * FROM recipes ORDER BY name");

        foreach ($recipes as &$recipe) {
            // Get ingredients
            $recipe['ingredients'] = Database::query(
                "SELECT product_name as name, quantity, unit, is_optional, notes
                 FROM recipe_ingredients
                 WHERE recipe_id = ?
                 ORDER BY id",
                [$recipe['id']]
            );

            // Parse JSON fields
            if ($recipe['instructions'] && $recipe['instructions'][0] === '[') {
                $recipe['instructions'] = json_decode($recipe['instructions'], true);
            }
            if ($recipe['tags'] && $recipe['tags'][0] === '[') {
                $recipe['tags'] = json_decode($recipe['tags'], true);
            }

            // Remove internal id for cleaner export
            unset($recipe['id']);
        }

        $data['recipes'] = $recipes;
    }

    if ($type === 'all' || $type === 'prices') {
        // Export prices grouped by store
        if ($storeFilter) {
            // Export specific store only
            $stores = Database::query("SELECT * FROM stores WHERE slug = ? AND is_active = 1", [$storeFilter]);
        } else {
            $stores = Database::query("SELECT * FROM stores WHERE is_active = 1");
        }
        $pricesData = [];

        foreach ($stores as $store) {
            // Build query with optional category filter
            $sql = "SELECT store_product_name as name, price, original_price, discount_percent as discount,
                        weight, unit, category_slug as category, url, parsed_at
                 FROM prices
                 WHERE store_id = ? AND is_available = 1";
            $params = [$store['id']];

            if ($categoriesArray && count($categoriesArray) > 0) {
                $placeholders = implode(',', array_fill(0, count($categoriesArray), '?'));
                $sql .= " AND category_slug IN ($placeholders)";
                $params = array_merge($params, $categoriesArray);
            }

            $sql .= " ORDER BY category_slug, store_product_name";
            $prices = Database::query($sql, $params);

            if (!empty($prices)) {
                $pricesData[$store['slug']] = [
                    'store_name' => $store['name'],
                    'products' => $prices
                ];
            }
        }

        $data['prices'] = $pricesData;
    }

    if ($type === 'all' || $type === 'products') {
        // Export base products
        $products = Database::query(
            "SELECT p.name, c.slug as category, p.search_keywords, p.default_weight, p.default_unit
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY c.sort_order, p.name"
        );

        $data['products'] = $products;
    }

    if ($type === 'all' || $type === 'categories') {
        $data['categories'] = Database::query("SELECT slug, name, emoji, url_path, sort_order FROM categories ORDER BY sort_order");
    }

    if ($type === 'all' || $type === 'stores') {
        $data['stores'] = Database::query("SELECT slug, name, delivery_time_min, delivery_time_max, min_order FROM stores WHERE is_active = 1");
    }

    // Set filename
    $filename = "smartcart-{$type}";
    if ($storeFilter) {
        $filename .= "-{$storeFilter}";
    }
    if ($categoryFilter) {
        $filename .= "-" . str_replace(',', '_', $categoryFilter);
    }
    $filename .= "-" . date('Y-m-d') . ".json";
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    jsonResponse($data);

} elseif ($method === 'POST') {
    $data = getJsonBody();

    if (empty($data)) {
        errorResponse('No data provided');
    }

    $imported = [
        'recipes' => 0,
        'products' => 0,
        'prices' => 0
    ];

    // Import recipes
    if (!empty($data['recipes']) && is_array($data['recipes'])) {
        foreach ($data['recipes'] as $recipe) {
            if (empty($recipe['name'])) continue;

            // Check if recipe exists
            $existing = Database::query("SELECT id FROM recipes WHERE name = ?", [$recipe['name']]);

            if (!empty($existing)) {
                // Update existing
                $id = $existing[0]['id'];

                Database::execute(
                    "UPDATE recipes SET description = ?, instructions = ?, cook_time = ?, prep_time = ?,
                     servings = ?, equipment = ?, category = ?, tags = ?, image_svg = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [
                        $recipe['description'] ?? null,
                        is_array($recipe['instructions'] ?? null) ? json_encode($recipe['instructions'], JSON_UNESCAPED_UNICODE) : ($recipe['instructions'] ?? null),
                        $recipe['cook_time'] ?? null,
                        $recipe['prep_time'] ?? null,
                        $recipe['servings'] ?? 2,
                        $recipe['equipment'] ?? null,
                        $recipe['category'] ?? null,
                        is_array($recipe['tags'] ?? null) ? json_encode($recipe['tags'], JSON_UNESCAPED_UNICODE) : ($recipe['tags'] ?? null),
                        $recipe['image_svg'] ?? null,
                        $id
                    ]
                );

                // Update ingredients
                Database::execute("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);

            } else {
                // Insert new
                Database::execute(
                    "INSERT INTO recipes (name, description, instructions, cook_time, prep_time, servings, equipment, category, tags, image_svg)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $recipe['name'],
                        $recipe['description'] ?? null,
                        is_array($recipe['instructions'] ?? null) ? json_encode($recipe['instructions'], JSON_UNESCAPED_UNICODE) : ($recipe['instructions'] ?? null),
                        $recipe['cook_time'] ?? null,
                        $recipe['prep_time'] ?? null,
                        $recipe['servings'] ?? 2,
                        $recipe['equipment'] ?? null,
                        $recipe['category'] ?? null,
                        is_array($recipe['tags'] ?? null) ? json_encode($recipe['tags'], JSON_UNESCAPED_UNICODE) : ($recipe['tags'] ?? null),
                        $recipe['image_svg'] ?? null
                    ]
                );

                $id = Database::lastInsertId();
            }

            // Add ingredients
            if (!empty($recipe['ingredients'])) {
                foreach ($recipe['ingredients'] as $ing) {
                    if (empty($ing['name'])) continue;

                    // Try to match product
                    $productId = null;
                    $products = Database::query("SELECT id, search_keywords FROM products");
                    foreach ($products as $p) {
                        if (matchProduct($ing['name'], $p['search_keywords'] ?? '')) {
                            $productId = $p['id'];
                            break;
                        }
                    }

                    Database::execute(
                        "INSERT INTO recipe_ingredients (recipe_id, product_id, product_name, quantity, unit, is_optional, notes)
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $id,
                            $productId,
                            $ing['name'],
                            $ing['quantity'] ?? 1,
                            $ing['unit'] ?? null,
                            $ing['is_optional'] ?? 0,
                            $ing['notes'] ?? null
                        ]
                    );
                }
            }

            $imported['recipes']++;
        }
    }

    // Import products
    if (!empty($data['products']) && is_array($data['products'])) {
        foreach ($data['products'] as $product) {
            if (empty($product['name'])) continue;

            // Get category id
            $categoryId = null;
            if (!empty($product['category'])) {
                $cat = Database::query("SELECT id FROM categories WHERE slug = ?", [$product['category']]);
                $categoryId = $cat[0]['id'] ?? null;
            }

            // Check if exists
            $existing = Database::query("SELECT id FROM products WHERE name = ?", [$product['name']]);

            if (empty($existing)) {
                Database::execute(
                    "INSERT INTO products (name, category_id, search_keywords, default_weight, default_unit)
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $product['name'],
                        $categoryId,
                        $product['search_keywords'] ?? $product['name'],
                        $product['default_weight'] ?? null,
                        $product['default_unit'] ?? 'г'
                    ]
                );
                $imported['products']++;
            }
        }
    }

    // Import prices
    if (!empty($data['prices']) && is_array($data['prices'])) {
        foreach ($data['prices'] as $storeSlug => $storeData) {
            $store = Database::query("SELECT id FROM stores WHERE slug = ?", [$storeSlug]);
            if (empty($store)) continue;

            $storeId = $store[0]['id'];
            $products = $storeData['products'] ?? [];

            foreach ($products as $price) {
                if (empty($price['name']) || empty($price['price'])) continue;

                $pricePerKg = calculatePricePerKg($price['price'], $price['weight'] ?? null, $price['unit'] ?? 'г');

                // Check if exists
                $existing = Database::query(
                    "SELECT id FROM prices WHERE store_id = ? AND store_product_name = ?",
                    [$storeId, $price['name']]
                );

                if (!empty($existing)) {
                    Database::execute(
                        "UPDATE prices SET price = ?, original_price = ?, discount_percent = ?,
                         weight = ?, unit = ?, price_per_kg = ?, category_slug = ?, url = ?, parsed_at = CURRENT_TIMESTAMP
                         WHERE id = ?",
                        [
                            $price['price'],
                            $price['original_price'] ?? null,
                            $price['discount'] ?? null,
                            $price['weight'] ?? null,
                            $price['unit'] ?? 'г',
                            $pricePerKg,
                            $price['category'] ?? null,
                            $price['url'] ?? null,
                            $existing[0]['id']
                        ]
                    );
                } else {
                    Database::execute(
                        "INSERT INTO prices (store_id, store_product_name, price, original_price, discount_percent, weight, unit, price_per_kg, category_slug, url)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $storeId,
                            $price['name'],
                            $price['price'],
                            $price['original_price'] ?? null,
                            $price['discount'] ?? null,
                            $price['weight'] ?? null,
                            $price['unit'] ?? 'г',
                            $pricePerKg,
                            $price['category'] ?? null,
                            $price['url'] ?? null
                        ]
                    );
                }

                $imported['prices']++;
            }
        }
    }

    jsonResponse([
        'success' => true,
        'imported' => $imported
    ]);

} else {
    errorResponse('Method not allowed', 405);
}
