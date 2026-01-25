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

// Common exclusions for processed foods
$commonExclude = ['приправ', 'соус', 'маринад', 'смесь', 'бульон', 'для приготовления', 'салат', 'готов', 'суп', 'лапша'];

// Mapping rules: ingredient name -> search terms (no category filter - rely on exclusions)
$ingredientMapping = [
    // Курица (ищем сырое мясо)
    'Грудка куриная' => ['terms' => ['филе куриное', 'филе грудки', 'грудка'], 'exclude' => array_merge($commonExclude, ['колбас', 'сосис', 'пельмен'])],
    'Голень куриная' => ['terms' => ['голень кур', 'голени кур'], 'exclude' => $commonExclude],
    'Бедро куриное' => ['terms' => ['филе бедра', 'бедро кур', 'бедра кур'], 'exclude' => $commonExclude],

    // Индейка
    'Филе индейки' => ['terms' => ['филе индейки', 'индейка филе'], 'exclude' => $commonExclude],
    'Голень индейки' => ['terms' => ['голень индейки'], 'exclude' => $commonExclude],

    // Рыба
    'Пангасиус филе' => ['terms' => ['пангасиус'], 'exclude' => ['палочки', 'панирован']],

    // Молочные
    'Молоко 3.2%' => ['terms' => ['молоко 3,2', 'молоко 3.2', 'молоко пастеризованное'], 'exclude' => ['сгущ', 'сух', 'кокос', 'соевое', 'овсяное', 'миндальное']],
    'Масло сливочное' => ['terms' => ['масло сливочное', 'масло крестьянское'], 'exclude' => ['спред']],
    'Масло оливковое' => ['terms' => ['масло оливковое'], 'exclude' => []],
    'Масло растительное' => ['terms' => ['масло подсолнечное', 'масло растительное'], 'exclude' => []],

    // Крупы (убираем category filter - гречка может быть в pasta)
    'Гречка' => ['terms' => ['гречка ядрица', 'крупа гречневая', 'ядрица'], 'exclude' => ['смесь', 'каша', 'хлопья', 'ассорти', 'волшебное']],
    'Рис' => ['terms' => ['рис длиннозерн', 'рис круглозерн', 'рис белый', 'рис шлифованный'], 'exclude' => ['бумага', 'уксус', 'лапша', 'смесь', 'ассорти']],
    'Овсянка' => ['terms' => ['хлопья овсяные', 'геркулес', 'овсянка'], 'exclude' => ['печенье', 'батончик', 'каша готов', 'мюсли']],

    // Овощи
    'Картофель' => ['terms' => ['картофель мытый', 'картофель красный', 'картофель белый'], 'exclude' => ['чипсы', 'пюре', 'замороженн', 'фри']],
    'Морковь' => ['terms' => ['морковь мытая', 'морковь свежая', 'морковь'], 'exclude' => ['сок', 'по-корейски', 'соус', 'хлебц']],
    'Лук репчатый' => ['terms' => ['лук репчатый', 'лук жёлтый', 'лук белый'], 'exclude' => ['семена', 'маринован', 'сушен', 'чипсы', 'колечки', 'криспы', 'хлебц', 'каша', 'сыр']],
    'Капуста белокочанная' => ['terms' => ['капуста белокочанная'], 'exclude' => ['квашен', 'маринован', 'салат']],
    'Помидоры' => ['terms' => ['помидоры', 'томаты черри', 'томаты'], 'exclude' => ['паста', 'соус', 'кетчуп', 'сушен', 'вялен', 'консерв']],
    'Огурцы' => ['terms' => ['огурцы свежие', 'огурцы'], 'exclude' => ['маринован', 'солен', 'малосол', 'консерв']],
    'Яблоки' => ['terms' => ['яблоки', 'яблоко'], 'exclude' => ['сок', 'пюре', 'сушен', 'чипсы', 'уксус']],

    // Яйца
    'Яйца С1' => ['terms' => ['яйца С1', 'яйца куриные С1'], 'exclude' => ['перепел', 'порошок']],
    'Яйца С0' => ['terms' => ['яйца С0', 'яйца куриные С0'], 'exclude' => ['перепел']],
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
        'exclude' => $commonExclude
    ];

    $bestMatch = null;
    $bestPrice = PHP_FLOAT_MAX;

    foreach ($mapping['terms'] as $term) {
        // Build query - no category filter, rely on exclusions
        $sql = "SELECT store_product_name as name, price, weight, unit, category_slug
                FROM prices
                WHERE store_id = ? AND is_available = 1
                AND LOWER(store_product_name) LIKE ?
                ORDER BY price ASC LIMIT 30";
        $params = [$storeId, '%' . mb_strtolower($term) . '%'];

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
