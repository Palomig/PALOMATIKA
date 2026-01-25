<?php
$productId = (int)($_GET['id'] ?? 0);

$product = Database::query(
    "SELECT p.*, c.name as category_name, c.emoji as category_emoji
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id = ?",
    [$productId]
);

if (empty($product)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$product = $product[0];
$pageTitle = $product['name'];
$currentPage = 'products';

// Get all prices for this product
$prices = Database::query(
    "SELECT pr.*, s.name as store_name, s.slug as store_slug
     FROM prices pr
     JOIN stores s ON pr.store_id = s.id
     WHERE pr.product_id = ? AND pr.is_available = 1
     ORDER BY pr.price ASC",
    [$productId]
);

// Get recipes using this product
$recipes = Database::query(
    "SELECT DISTINCT r.*
     FROM recipes r
     JOIN recipe_ingredients ri ON r.id = ri.recipe_id
     WHERE ri.product_id = ?
     ORDER BY r.name",
    [$productId]
);

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <a href="<?= BASE_URL ?>/products" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 10px;">
        ← Все продукты
    </a>
    <h1 class="page-title">
        <?= $product['category_emoji'] ?? '📦' ?>
        <?= htmlspecialchars($product['name']) ?>
    </h1>
    <div style="color: var(--text-secondary); margin-top: 8px;">
        <?= $product['category_name'] ?? 'Без категории' ?>
        <?php if ($product['default_weight']): ?>
            • <?= $product['default_weight'] ?> <?= $product['default_unit'] ?>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px;">
    <!-- Prices -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">💰 Цены в магазинах</h2>
        </div>

        <?php if (empty($prices)): ?>
            <div class="empty-state" style="padding: 30px;">
                <p>Цены ещё не спарсены</p>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Используйте расширение для сбора цен</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($prices as $i => $price): ?>
                    <div class="store-row <?= $i === 0 ? 'best' : '' ?>">
                        <div>
                            <div style="font-weight: 500;"><?= $price['store_name'] ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= htmlspecialchars($price['store_product_name']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                <?php if ($price['weight']): ?>
                                    <?= $price['weight'] ?> <?= $price['unit'] ?>
                                <?php endif; ?>
                                <?php if ($price['price_per_kg']): ?>
                                    • <?= formatPrice($price['price_per_kg']) ?>/кг
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: var(--font-mono); font-weight: 700; font-size: 1.2rem; color: <?= $i === 0 ? 'var(--accent-green)' : 'var(--text-primary)' ?>;">
                                <?= formatPrice($price['price']) ?>
                            </div>
                            <?php if ($price['discount_percent']): ?>
                                <div>
                                    <span class="badge badge-danger">-<?= round($price['discount_percent']) ?>%</span>
                                    <span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.85rem; margin-left: 4px;">
                                        <?= formatPrice($price['original_price']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                <?= relativeTime($price['parsed_at']) ?>
                            </div>
                        </div>
                        <?php if ($price['url']): ?>
                            <a href="<?= htmlspecialchars($price['url']) ?>" target="_blank" class="btn btn-sm btn-secondary" style="margin-left: 12px;">
                                →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Add to Cart -->
        <div class="card" style="margin-bottom: 24px;">
            <button class="btn btn-primary" style="width: 100%;" onclick="addToCart()">
                🛒 Добавить в корзину
            </button>
        </div>

        <!-- Product Info -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">📋 Информация</h2>
            </div>

            <dl style="display: grid; gap: 12px;">
                <div>
                    <dt style="color: var(--text-secondary); font-size: 0.85rem;">Категория</dt>
                    <dd style="font-weight: 500;"><?= $product['category_emoji'] ?? '' ?> <?= $product['category_name'] ?? '—' ?></dd>
                </div>
                <?php if ($product['default_weight']): ?>
                    <div>
                        <dt style="color: var(--text-secondary); font-size: 0.85rem;">Стандартный вес</dt>
                        <dd style="font-weight: 500;"><?= $product['default_weight'] ?> <?= $product['default_unit'] ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ($product['search_keywords']): ?>
                    <div>
                        <dt style="color: var(--text-secondary); font-size: 0.85rem;">Ключевые слова</dt>
                        <dd style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($product['search_keywords']) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Used in Recipes -->
        <?php if (!empty($recipes)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📖 В рецептах</h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($recipes as $recipe): ?>
                        <a href="<?= BASE_URL ?>/recipes/<?= $recipe['id'] ?>" style="padding: 10px; background: var(--bg-tertiary); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                            <span><?= htmlspecialchars($recipe['name']) ?></span>
                            <span style="color: var(--text-muted);">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$productName = htmlspecialchars($product['name'], ENT_QUOTES);
$pageScripts = <<<JS
<script>
async function addToCart() {
    try {
        await fetch(BASE_URL + '/api/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: {$productId},
                name: '{$productName}'
            })
        });

        showToast('{$productName} добавлен в корзину', 'success');
    } catch (e) {
        showToast('Ошибка добавления', 'error');
    }
}
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
