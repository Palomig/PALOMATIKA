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

// Version for tracking deployments
define('API_VERSION', '3.0');

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
    // Курица (ищем сырое мясо, исключаем индейку и готовые продукты)
    'Грудка куриная' => ['terms' => ['Филе куриное', 'Грудка кур', 'грудка куриная'], 'exclude' => array_merge($commonExclude, ['колбас', 'сосис', 'пельмен', 'фарш', 'Фарш', 'индейк', 'Индейк', 'ветчина', 'Ветчина'])],
    'Голень куриная' => ['terms' => ['Голень кур', 'Голени кур'], 'exclude' => array_merge($commonExclude, ['индейк', 'Индейк'])],
    'Бедро куриное' => ['terms' => ['Филе бедра', 'Бедро кур', 'Бедра кур'], 'exclude' => array_merge($commonExclude, ['индейк', 'Индейк'])],

    // Индейка
    'Филе индейки' => ['terms' => ['Филе индейки', 'Индейка филе'], 'exclude' => $commonExclude],
    'Голень индейки' => ['terms' => ['Голень индейки'], 'exclude' => $commonExclude],

    // Рыба
    'Пангасиус филе' => ['terms' => ['Пангасиус', 'пангасиус'], 'exclude' => ['палочки', 'панирован']],

    // Молочные
    'Молоко 3.2%' => ['terms' => ['Молоко 3,2', 'Молоко пастеризованное', 'Молоко 3.2'], 'exclude' => ['сгущ', 'сух', 'кокос', 'соевое', 'овсяное', 'миндальное']],
    'Масло сливочное' => ['terms' => ['Масло сливочное', 'Масло крестьянское'], 'exclude' => ['спред']],
    'Масло оливковое' => ['terms' => ['Масло оливковое'], 'exclude' => []],
    'Масло растительное' => ['terms' => ['Масло подсолнечное', 'Масло растительное'], 'exclude' => []],

    // Крупы (используем точное написание как в базе)
    'Гречка' => ['terms' => ['Гречка ядрица', 'Крупа гречневая', 'ядрица'], 'exclude' => ['Смесь', 'смесь', 'каша', 'хлопья', 'ассорти', 'Ассорти', 'Волшебное']],
    'Рис' => ['terms' => ['Рис длиннозерн', 'Рис круглозерн', 'Рис белый', 'Рис шлифованный'], 'exclude' => ['бумага', 'уксус', 'лапша', 'смесь', 'ассорти']],
    'Овсянка' => ['terms' => ['Хлопья овсяные', 'Геркулес', 'Овсянка'], 'exclude' => ['печенье', 'батончик', 'каша готов', 'мюсли']],

    // Овощи (используем точное написание как в базе)
    'Картофель' => ['terms' => ['Картофель мытый', 'Картофель красный', 'Картофель'], 'exclude' => ['чипсы', 'пюре', 'замороженн', 'фри']],
    'Морковь' => ['terms' => ['Морковь мытая', 'Морковь свежая', 'Морковь'], 'exclude' => ['сок', 'по-корейски', 'соус', 'хлебц']],
    'Лук репчатый' => ['terms' => ['Лук репчатый', 'Лук жёлтый', 'Лук белый'], 'exclude' => ['Kotanyi', 'семена', 'маринован', 'сушен', 'чипсы', 'колечки', 'криспы', 'хлебц', 'каша', 'сыр']],
    'Капуста белокочанная' => ['terms' => ['Капуста белокочанная'], 'exclude' => ['квашен', 'маринован', 'салат']],
    'Помидоры' => ['terms' => ['Помидоры', 'Томаты черри', 'Томаты'], 'exclude' => ['паста', 'соус', 'кетчуп', 'сушен', 'вялен', 'консерв']],
    'Огурцы' => ['terms' => ['Огурцы свежие', 'Огурцы'], 'exclude' => ['маринован', 'солен', 'малосол', 'консерв']],
    'Яблоки' => ['terms' => ['Яблоки', 'Яблоко'], 'exclude' => ['сок', 'пюре', 'сушен', 'чипсы', 'уксус']],

    // Яйца
    'Яйца С1' => ['terms' => ['Яйца С1', 'Яйца куриные С1', 'Яйца куриные'], 'exclude' => ['перепел', 'порошок']],
    'Яйца С0' => ['terms' => ['Яйца С0', 'Яйца куриные С0'], 'exclude' => ['перепел']],
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
        // Note: COLLATE NOCASE doesn't work with Cyrillic UTF-8, use plain LIKE
        $sql = "SELECT store_product_name as name, price, weight, unit, category_slug
                FROM prices
                WHERE store_id = ? AND is_available = 1
                AND store_product_name LIKE ?
                ORDER BY price ASC LIMIT 30";
        $params = [$storeId, '%' . $term . '%'];

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
        $productWeight = (float)($bestMatch['weight'] ?: 1000);
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
            'cost' => round($ingredientCost, 2),
            'search_terms' => $mapping['terms']
        ];
    } else {
        $missingIngredients[] = $ingName;
        $results[] = [
            'ingredient' => $ingName,
            'needed' => $quantity . ' ' . $unit,
            'matched_product' => null,
            'cost' => 0,
            'error' => 'Не найден подходящий товар',
            'search_terms' => $mapping['terms']
        ];
    }
}

jsonResponse([
    'api_version' => API_VERSION,
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
