<?php
$recipeId = (int)($_GET['id'] ?? 0);

$recipe = Database::query("SELECT * FROM recipes WHERE id = ?", [$recipeId]);

if (empty($recipe)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$recipe = $recipe[0];
$pageTitle = $recipe['name'];
$currentPage = 'recipes';

// Get ingredients
$ingredients = Database::query(
    "SELECT ri.*, p.name as linked_product_name
     FROM recipe_ingredients ri
     LEFT JOIN products p ON ri.product_id = p.id
     WHERE ri.recipe_id = ?
     ORDER BY ri.id",
    [$recipeId]
);

// Calculate costs for all stores
$stores = Database::query("SELECT * FROM stores WHERE is_active = 1");
$storeCosts = [];

foreach ($stores as $store) {
    $cost = calculateRecipeCost($recipeId, $store['id']);
    $storeCosts[] = [
        'store' => $store,
        'cost' => $cost
    ];
}

// Sort by price
usort($storeCosts, fn($a, $b) => $a['cost']['total'] <=> $b['cost']['total']);

// Parse instructions
$instructions = $recipe['instructions'];
if ($instructions && $instructions[0] === '[') {
    $instructions = json_decode($instructions, true);
}

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <a href="<?= BASE_URL ?>/recipes" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 10px;">
            ← Все рецепты
        </a>
        <h1 class="page-title"><?= htmlspecialchars($recipe['name']) ?></h1>
        <div style="display: flex; gap: 16px; margin-top: 8px; color: var(--text-secondary);">
            <?php if ($recipe['cook_time']): ?>
                <span>⏱ <?= $recipe['cook_time'] ?> мин готовки</span>
            <?php endif; ?>
            <?php if ($recipe['prep_time']): ?>
                <span>🔪 <?= $recipe['prep_time'] ?> мин подготовки</span>
            <?php endif; ?>
            <?php if ($recipe['servings']): ?>
                <span>👥 <?= $recipe['servings'] ?> порций</span>
            <?php endif; ?>
        </div>
    </div>

    <button class="btn btn-primary" onclick="addAllToCart()">
        🛒 Добавить всё в корзину
    </button>
</div>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Description -->
        <?php if ($recipe['description']): ?>
            <div class="card" style="margin-bottom: 24px;">
                <p style="color: var(--text-secondary);"><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- Ingredients -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">📦 Ингредиенты</h2>
            </div>

            <ul class="ingredient-list">
                <?php foreach ($ingredients as $ing):
                    // Find best price for this ingredient
                    $price = null;
                    if ($ing['product_id']) {
                        $prices = Database::query(
                            "SELECT price FROM prices WHERE product_id = ? AND is_available = 1 ORDER BY price ASC LIMIT 1",
                            [$ing['product_id']]
                        );
                        $price = $prices[0]['price'] ?? null;
                    }
                ?>
                    <li class="ingredient-item">
                        <span class="ingredient-name">
                            <?= htmlspecialchars($ing['product_name']) ?>
                            <?php if ($ing['is_optional']): ?>
                                <span class="badge badge-info">опционально</span>
                            <?php endif; ?>
                        </span>
                        <span class="ingredient-quantity">
                            <?= $ing['quantity'] ?> <?= $ing['unit'] ?? 'г' ?>
                        </span>
                        <?php if ($price): ?>
                            <span class="ingredient-price">~<?= formatPrice($price) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Instructions -->
        <?php if ($instructions): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">👨‍🍳 Инструкция</h2>
                </div>

                <?php if (is_array($instructions)): ?>
                    <ol style="padding-left: 20px;">
                        <?php foreach ($instructions as $i => $step): ?>
                            <li style="margin-bottom: 16px; padding-left: 8px;">
                                <?= htmlspecialchars($step) ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <div style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($instructions)) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Image -->
        <?php if ($recipe['image_svg']): ?>
            <div class="card" style="margin-bottom: 24px; padding: 0; overflow: hidden;">
                <div style="padding: 40px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center;">
                    <?= $recipe['image_svg'] ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Store Comparison -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">💰 Сравнение цен</h2>
            </div>

            <div class="store-compare">
                <?php foreach ($storeCosts as $i => $sc): ?>
                    <div class="store-row <?= $i === 0 ? 'best' : '' ?>">
                        <div>
                            <div class="store-name"><?= $sc['store']['name'] ?></div>
                            <div class="store-delivery"><?= $sc['store']['delivery_time_min'] ?>-<?= $sc['store']['delivery_time_max'] ?> мин</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="store-total"><?= formatPrice($sc['cost']['total']) ?></div>
                            <?php if (!$sc['cost']['complete']): ?>
                                <div style="font-size: 0.75rem; color: var(--accent-yellow);">
                                    нет <?= count($sc['cost']['missing']) ?> товаров
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($i === 0): ?>
                            <span class="badge badge-success">Лучшая</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Equipment -->
        <?php if ($recipe['equipment']): ?>
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">🍳 Оборудование</h2>
                </div>
                <p><?= htmlspecialchars($recipe['equipment']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Tags -->
        <?php
        $tags = $recipe['tags'];
        if ($tags && $tags[0] === '[') {
            $tags = json_decode($tags, true);
        }
        ?>
        <?php if ($tags && is_array($tags)): ?>
            <div class="card" style="margin-top: 24px;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge badge-info"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = <<<JS
<script>
async function addAllToCart() {
    const ingredients = <?= json_encode(array_map(fn(\$i) => [
        'name' => \$i['product_name'],
        'quantity' => \$i['quantity'],
        'unit' => \$i['unit']
    ], \$ingredients)) ?>;

    try {
        const response = await fetch('<?= BASE_URL ?>/api/cart/bulk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: ingredients })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Добавлено ' + data.added + ' товаров в корзину', 'success');
        }
    } catch (e) {
        showToast('Ошибка добавления', 'error');
    }
}
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
