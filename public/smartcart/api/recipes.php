<?php
/**
 * SmartCart API - Recipes
 *
 * GET /api/recipes - List all recipes
 * GET /api/recipes/{id} - Get recipe with ingredients and costs
 * GET /api/recipes/{id}/cost?store={slug} - Calculate cost in specific store
 * POST /api/recipes - Create recipe
 * PUT /api/recipes/{id} - Update recipe
 * DELETE /api/recipes/{id} - Delete recipe
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($method === 'GET') {
    if ($id && $action === 'cost') {
        // GET /api/recipes/{id}/cost
        $storeSlug = $_GET['store'] ?? null;
        $storeId = null;

        if ($storeSlug) {
            $store = Database::query("SELECT id FROM stores WHERE slug = ?", [$storeSlug]);
            $storeId = $store[0]['id'] ?? null;
        }

        $cost = calculateRecipeCost((int)$id, $storeId);

        jsonResponse($cost);

    } elseif ($id) {
        // GET /api/recipes/{id}
        $recipe = Database::query("SELECT * FROM recipes WHERE id = ?", [$id]);

        if (empty($recipe)) {
            errorResponse('Recipe not found', 404);
        }

        // Get ingredients
        $ingredients = Database::query(
            "SELECT ri.*, p.name as linked_product_name, p.default_unit
             FROM recipe_ingredients ri
             LEFT JOIN products p ON ri.product_id = p.id
             WHERE ri.recipe_id = ?
             ORDER BY ri.id",
            [$id]
        );

        // Calculate cost for all stores
        $stores = Database::query("SELECT * FROM stores WHERE is_active = 1");
        $storeCosts = [];

        foreach ($stores as $store) {
            $cost = calculateRecipeCost((int)$id, $store['id']);
            $storeCosts[] = [
                'store' => [
                    'id' => $store['id'],
                    'slug' => $store['slug'],
                    'name' => $store['name']
                ],
                'total' => $cost['total'],
                'complete' => $cost['complete'],
                'missing' => $cost['missing']
            ];
        }

        // Sort by price
        usort($storeCosts, fn($a, $b) => $a['total'] <=> $b['total']);

        // Parse instructions if JSON
        $instructions = $recipe[0]['instructions'];
        if ($instructions && $instructions[0] === '[') {
            $recipe[0]['instructions_parsed'] = json_decode($instructions, true);
        }

        // Parse tags if JSON
        $tags = $recipe[0]['tags'];
        if ($tags && $tags[0] === '[') {
            $recipe[0]['tags_parsed'] = json_decode($tags, true);
        }

        jsonResponse([
            'recipe' => $recipe[0],
            'ingredients' => $ingredients,
            'store_costs' => $storeCosts,
            'best_price' => $storeCosts[0]['total'] ?? 0
        ]);

    } else {
        // GET /api/recipes
        $category = $_GET['category'] ?? null;
        $equipment = $_GET['equipment'] ?? null;
        $favorite = isset($_GET['favorite']);

        $sql = "SELECT * FROM recipes WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        if ($equipment) {
            $sql .= " AND equipment LIKE ?";
            $params[] = "%{$equipment}%";
        }
        if ($favorite) {
            $sql .= " AND is_favorite = 1";
        }

        $sql .= " ORDER BY is_favorite DESC, times_cooked DESC, name";

        $recipes = Database::query($sql, $params);

        // Add best price for each recipe
        foreach ($recipes as &$recipe) {
            $cost = calculateRecipeCost($recipe['id']);
            $recipe['best_price'] = $cost['total'];
            $recipe['ingredients_complete'] = $cost['complete'];

            // Count ingredients
            $ingCount = Database::query(
                "SELECT COUNT(*) as cnt FROM recipe_ingredients WHERE recipe_id = ?",
                [$recipe['id']]
            );
            $recipe['ingredients_count'] = $ingCount[0]['cnt'];

            // Parse tags
            if ($recipe['tags'] && $recipe['tags'][0] === '[') {
                $recipe['tags_parsed'] = json_decode($recipe['tags'], true);
            }
        }

        jsonResponse(['recipes' => $recipes]);
    }

} elseif ($method === 'POST') {
    $data = getJsonBody();

    if (empty($data['name'])) {
        errorResponse('name is required');
    }

    // Insert recipe
    Database::execute(
        "INSERT INTO recipes (name, description, instructions, cook_time, prep_time, servings, equipment, category, tags, image_svg, is_favorite)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $data['name'],
            $data['description'] ?? null,
            is_array($data['instructions'] ?? null) ? json_encode($data['instructions'], JSON_UNESCAPED_UNICODE) : ($data['instructions'] ?? null),
            $data['cook_time'] ?? null,
            $data['prep_time'] ?? null,
            $data['servings'] ?? 2,
            $data['equipment'] ?? null,
            $data['category'] ?? null,
            is_array($data['tags'] ?? null) ? json_encode($data['tags'], JSON_UNESCAPED_UNICODE) : ($data['tags'] ?? null),
            $data['image_svg'] ?? null,
            $data['is_favorite'] ?? 0
        ]
    );

    $recipeId = Database::lastInsertId();

    // Add ingredients
    if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
        foreach ($data['ingredients'] as $ing) {
            if (empty($ing['name'])) continue;

            // Try to match with existing product
            $productId = null;
            if (!empty($ing['product_id'])) {
                $productId = $ing['product_id'];
            } else {
                $products = Database::query("SELECT id, search_keywords FROM products");
                foreach ($products as $p) {
                    if (matchProduct($ing['name'], $p['search_keywords'] ?? '')) {
                        $productId = $p['id'];
                        break;
                    }
                }
            }

            Database::execute(
                "INSERT INTO recipe_ingredients (recipe_id, product_id, product_name, quantity, unit, is_optional, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $recipeId,
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

    jsonResponse([
        'success' => true,
        'id' => $recipeId
    ], 201);

} elseif ($method === 'PUT') {
    if (!$id) {
        errorResponse('Recipe ID is required');
    }

    $data = getJsonBody();

    $fields = [];
    $params = [];

    $allowedFields = ['name', 'description', 'cook_time', 'prep_time', 'servings', 'equipment', 'category', 'image_svg', 'is_favorite'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    // Handle JSON fields
    if (isset($data['instructions'])) {
        $fields[] = "instructions = ?";
        $params[] = is_array($data['instructions']) ? json_encode($data['instructions'], JSON_UNESCAPED_UNICODE) : $data['instructions'];
    }
    if (isset($data['tags'])) {
        $fields[] = "tags = ?";
        $params[] = is_array($data['tags']) ? json_encode($data['tags'], JSON_UNESCAPED_UNICODE) : $data['tags'];
    }

    $fields[] = "updated_at = CURRENT_TIMESTAMP";

    if (count($fields) <= 1) {
        errorResponse('No fields to update');
    }

    $params[] = $id;
    Database::execute(
        "UPDATE recipes SET " . implode(', ', $fields) . " WHERE id = ?",
        $params
    );

    // Update ingredients if provided
    if (isset($data['ingredients']) && is_array($data['ingredients'])) {
        // Remove old ingredients
        Database::execute("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);

        // Add new ingredients
        foreach ($data['ingredients'] as $ing) {
            if (empty($ing['name'])) continue;

            $productId = $ing['product_id'] ?? null;

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

    jsonResponse(['success' => true]);

} elseif ($method === 'DELETE') {
    if (!$id) {
        errorResponse('Recipe ID is required');
    }

    Database::execute("DELETE FROM recipes WHERE id = ?", [$id]);

    jsonResponse(['success' => true]);

} else {
    errorResponse('Method not allowed', 405);
}
