<?php
/**
 * SmartCart API - Recipe Cost Calculator
 *
 * GET /api/recipe-cost.php?id=1 - Calculate cost for recipe
 * GET /api/recipe-cost.php?id=1&store=perekrestok - For specific store
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

setCorsHeaders();

$recipeId = $_GET['id'] ?? null;
$storeSlug = $_GET['store'] ?? 'perekrestok';

if (!$recipeId) {
    errorResponse('Recipe ID is required');
}

// Get recipe
$recipe = Database::query("SELECT * FROM recipes WHERE id = ?", [$recipeId]);
if (empty($recipe)) {
    errorResponse('Recipe not found', 404);
}
$recipe = $recipe[0];

// Get store
$store = Database::query("SELECT id FROM stores WHERE slug = ?", [$storeSlug]);
if (empty($store)) {
    errorResponse('Store not found', 404);
}
$storeId = $store[0]['id'];

// Get ingredients
$ingredients = Database::query(
    "SELECT ri.*, p.search_keywords
     FROM recipe_ingredients ri
     LEFT JOIN products p ON ri.product_id = p.id
     WHERE ri.recipe_id = ?",
    [$recipeId]
);

// Mapping rules: ingredient name -> search terms + category filter
$ingredientMapping = [
    // Курица
    'Грудка куриная' => ['terms' => ['филе грудки', 'филе куриное'], 'category' => 'chicken', 'exclude' => ['приправ', 'соус', 'бульон', 'маринад']],
    'Голень куриная' => ['terms' => ['голень кур', 'голени кур'], 'category' => 'chicken', 'exclude' => ['приправ', 'соус']],
    'Бедро куриное' => ['terms' => ['бедро', 'бедра', 'филе бедра'], 'category' => 'chicken', 'exclude' => ['приправ', 'соус']],

    // Индейка
    'Филе индейки' => ['terms' => ['филе индейки', 'индейка филе'], 'category' => 'turkey', 'exclude' => ['приправ', 'соус', 'корм']],
    'Голень индейки' => ['terms' => ['голень индейки'], 'category' => 'turkey', 'exclude' => ['приправ', 'соус']],

    // Рыба
    'Пангасиус филе' => ['terms' => ['пангасиус'], 'category' => 'fish', 'exclude' => []],

    // Молочные
    'Молоко 3.2%' => ['terms' => ['молоко 3,2', 'молоко 3.2', 'молоко пастер'], 'category' => 'milk', 'exclude' => ['сгущ', 'сух', 'кокос']],
    'Масло сливочное' => ['terms' => ['масло сливочное', 'масло крестьян'], 'category' => 'butter', 'exclude' => []],
    'Масло оливковое' => ['terms' => ['масло оливковое'], 'category' => null, 'exclude' => []],
    'Масло растительное' => ['terms' => ['масло подсолнечное', 'масло растительное'], 'category' => null, 'exclude' => []],

    // Крупы
    'Гречка' => ['terms' => ['гречка', 'гречневая', 'ядрица'], 'category' => 'buckwheat', 'exclude' => ['хлопья', 'каша готов']],
    'Рис' => ['terms' => ['рис круглозерн', 'рис длиннозерн', 'рис белый'], 'category' => 'rice', 'exclude' => ['бумага', 'уксус', 'лапша']],
    'Овсянка' => ['terms' => ['овсянка', 'овсяные хлопья', 'геркулес'], 'category' => 'oatmeal', 'exclude' => ['печенье', 'батончик']],

    // Овощи
    'Картофель' => ['terms' => ['картофель', 'картошка'], 'category' => 'potato', 'exclude' => ['чипсы', 'пюре сух', 'замороженн']],
    'Морковь' => ['terms' => ['морковь'], 'category' => 'carrot', 'exclude' => ['сок', 'по-корейски', 'соус']],
    'Лук репчатый' => ['terms' => ['лук репчатый', 'лук желтый', 'лук белый'], 'category' => 'onion', 'exclude' => ['маринован', 'сушен', 'порошок']],
    'Капуста белокочанная' => ['terms' => ['капуста белокочанная'], 'category' => 'cabbage', 'exclude' => ['квашен', 'маринован', 'салат']],
    'Помидоры' => ['terms' => ['помидоры', 'томаты'], 'category' => 'tomato', 'exclude' => ['паста', 'соус', 'кетчуп', 'сушен', 'вялен']],
    'Огурцы' => ['terms' => ['огурцы'], 'category' => 'cucumber', 'exclude' => ['маринован', 'солен', 'малосол']],
    'Яблоки' => ['terms' => ['яблоки', 'яблоко'], 'category' => null, 'exclude' => ['сок', 'пюре', 'сушен', 'чипсы']],

    // Яйца
    'Яйца С1' => ['terms' => ['яйца С1', 'яйцо С1', 'яйца куриные'], 'category' => 'eggs', 'exclude' => ['перепел', 'порошок']],
    'Яйца С0' => ['terms' => ['яйца С0', 'яйцо С0'], 'category' => 'eggs', 'exclude' => ['перепел']],
];

// Process each ingredient
$results = [];
$totalCost = 0;
$missingIngredients = [];

foreach ($ingredients as $ing) {
    $ingName = $ing['product_name'];
    $quantity = $ing['quantity'];
    $unit = $ing['unit'];

    // Get mapping or create default
    $mapping = $ingredientMapping[$ingName] ?? [
        'terms' => [mb_strtolower($ingName)],
        'category' => null,
        'exclude' => ['приправ', 'соус', 'маринад', 'смесь для']
    ];

    $bestMatch = null;
    $bestPrice = PHP_FLOAT_MAX;

    foreach ($mapping['terms'] as $term) {
        // Build query
        $sql = "SELECT store_product_name as name, price, weight, unit, category_slug
                FROM prices
                WHERE store_id = ? AND is_available = 1
                AND LOWER(store_product_name) LIKE ?";
        $params = [$storeId, '%' . mb_strtolower($term) . '%'];

        // Add category filter if specified
        if ($mapping['category']) {
            $sql .= " AND category_slug = ?";
            $params[] = $mapping['category'];
        }

        $sql .= " ORDER BY price ASC LIMIT 20";

        $matches = Database::query($sql, $params);

        foreach ($matches as $match) {
            // Check exclusions
            $excluded = false;
            foreach ($mapping['exclude'] as $excl) {
                if (mb_stripos($match['name'], $excl) !== false) {
                    $excluded = true;
                    break;
                }
            }

            if (!$excluded && $match['price'] < $bestPrice) {
                $bestPrice = $match['price'];
                $bestMatch = $match;
            }
        }
    }

    if ($bestMatch) {
        // Calculate cost based on quantity needed
        $productWeight = $bestMatch['weight'] ?: 1000;
        $productUnit = $bestMatch['unit'] ?: 'г';

        // Convert units
        $neededGrams = $quantity;
        if ($unit === 'кг') $neededGrams = $quantity * 1000;
        if ($unit === 'мл') $neededGrams = $quantity;
        if ($unit === 'л') $neededGrams = $quantity * 1000;
        if ($unit === 'шт') $neededGrams = $quantity;

        // Calculate how many packages needed
        if ($unit === 'шт' && $productUnit === 'шт') {
            $packagesNeeded = ceil($neededGrams / $productWeight);
        } else {
            $packagesNeeded = ceil($neededGrams / $productWeight);
        }

        $ingredientCost = $bestMatch['price'] * $packagesNeeded;
        $totalCost += $ingredientCost;

        $results[] = [
            'ingredient' => $ingName,
            'needed' => $quantity . ' ' . $unit,
            'matched_product' => $bestMatch['name'],
            'product_price' => $bestMatch['price'],
            'product_weight' => $productWeight . ' ' . $productUnit,
            'packages_needed' => $packagesNeeded,
            'cost' => round($ingredientCost, 2)
        ];
    } else {
        $missingIngredients[] = $ingName;
        $results[] = [
            'ingredient' => $ingName,
            'needed' => $quantity . ' ' . $unit,
            'matched_product' => null,
            'cost' => 0,
            'error' => 'Не найден подходящий товар'
        ];
    }
}

jsonResponse([
    'recipe' => [
        'id' => $recipe['id'],
        'name' => $recipe['name'],
        'servings' => $recipe['servings']
    ],
    'store' => $storeSlug,
    'ingredients' => $results,
    'total_cost' => round($totalCost, 2),
    'cost_per_serving' => round($totalCost / ($recipe['servings'] ?: 1), 2),
    'missing_ingredients' => $missingIngredients
]);
