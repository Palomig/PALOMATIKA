<?php
$pageTitle = 'Дашборд';
$currentPage = 'dashboard';

// Get recent recipes
$recentRecipes = Database::query("SELECT * FROM recipes ORDER BY created_at DESC LIMIT 4");

// Get top discounts
$topDiscounts = Database::query(
    "SELECT p.*, s.name as store_name
     FROM prices p
     JOIN stores s ON p.store_id = s.id
     WHERE p.discount_percent > 0 AND p.is_available = 1
     ORDER BY p.discount_percent DESC
     LIMIT 5"
);

// Get stores with stats
$stores = Database::query(
    "SELECT s.*, COUNT(p.id) as products_count, MAX(p.parsed_at) as last_parsed
     FROM stores s
     LEFT JOIN prices p ON s.id = p.store_id AND p.is_available = 1
     WHERE s.is_active = 1
     GROUP BY s.id
     ORDER BY products_count DESC"
);

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Дашборд</h1>
    <p class="page-subtitle">Обзор системы мониторинга цен</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="card-icon cyan">📦</div>
        <div>
            <div class="stat-value"><?= $stats['products'] ?></div>
            <div class="stat-label">Продуктов в базе</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon purple">💰</div>
        <div>
            <div class="stat-value"><?= $stats['prices'] ?></div>
            <div class="stat-label">Спарсенных цен</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon green">📖</div>
        <div>
            <div class="stat-value"><?= $stats['recipes'] ?></div>
            <div class="stat-label">Рецептов</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon yellow">🛒</div>
        <div>
            <div class="stat-value"><?= $stats['cart_items'] ?></div>
            <div class="stat-label">В корзине</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">Быстрые действия</h2>
    </div>
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/recipes" class="quick-action">
            <span class="quick-action-icon">📖</span>
            <span class="quick-action-label">Все рецепты</span>
        </a>
        <a href="<?= BASE_URL ?>/cart" class="quick-action">
            <span class="quick-action-icon">🛒</span>
            <span class="quick-action-label">Корзина</span>
        </a>
        <a href="<?= BASE_URL ?>/prices" class="quick-action">
            <span class="quick-action-icon">💰</span>
            <span class="quick-action-label">Все цены</span>
        </a>
        <a href="<?= BASE_URL ?>/settings" class="quick-action">
            <span class="quick-action-icon">⚙️</span>
            <span class="quick-action-label">Настройки</span>
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Top Discounts -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🔥 Лучшие скидки</h2>
            <a href="<?= BASE_URL ?>/prices?sort=discount" class="btn btn-sm btn-secondary">Все →</a>
        </div>

        <?php if (empty($topDiscounts)): ?>
            <div class="empty-state" style="padding: 30px;">
                <p>Скидки появятся после парсинга цен</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($topDiscounts as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 4px;"><?= htmlspecialchars(mb_substr($item['store_product_name'], 0, 35)) ?><?= mb_strlen($item['store_product_name']) > 35 ? '...' : '' ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= $item['store_name'] ?></div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-danger">-<?= round($item['discount_percent']) ?>%</span>
                            <div style="font-family: var(--font-mono); color: var(--accent-green); margin-top: 4px;"><?= formatPrice($item['price']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stores Status -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏪 Магазины</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($stores as $store): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 500;"><?= $store['name'] ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?php if ($store['last_parsed']): ?>
                                <?= relativeTime($store['last_parsed']) ?>
                            <?php else: ?>
                                не спарсен
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-family: var(--font-mono); color: var(--accent-cyan);"><?= $store['products_count'] ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">товаров</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Recipes -->
<?php if (!empty($recentRecipes)): ?>
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h2 class="card-title">📖 Последние рецепты</h2>
        <a href="<?= BASE_URL ?>/recipes" class="btn btn-sm btn-secondary">Все рецепты →</a>
    </div>

    <div class="recipes-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
        <?php foreach ($recentRecipes as $recipe):
            $cost = calculateRecipeCost($recipe['id']);
        ?>
            <a href="<?= BASE_URL ?>/recipes/<?= $recipe['id'] ?>" class="recipe-card" style="text-decoration: none;">
                <div class="recipe-image">
                    <?php if ($recipe['image_svg']): ?>
                        <?= $recipe['image_svg'] ?>
                    <?php else: ?>
                        <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--accent-cyan); opacity: 0.3;">
                            <circle cx="50" cy="35" r="20"/>
                            <path d="M25 80 Q50 55 75 80" stroke-width="2"/>
                            <circle cx="35" cy="75" r="3" fill="currentColor"/>
                            <circle cx="65" cy="75" r="3" fill="currentColor"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="recipe-content">
                    <h3 class="recipe-name"><?= htmlspecialchars($recipe['name']) ?></h3>
                    <div class="recipe-meta">
                        <?php if ($recipe['cook_time']): ?>
                            <span>⏱ <?= $recipe['cook_time'] ?> мин</span>
                        <?php endif; ?>
                        <?php if ($recipe['equipment']): ?>
                            <span>🍳 <?= htmlspecialchars($recipe['equipment']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="recipe-price">
                        <span class="price-value">от <?= formatPrice($cost['total']) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
