<?php
$pageTitle = 'Аналитика';
$currentPage = 'analytics';

// Get statistics
$pricesByCategory = Database::query(
    "SELECT p.category_slug, COUNT(*) as cnt, AVG(p.price) as avg_price, MIN(p.price) as min_price, MAX(p.price) as max_price
     FROM prices p
     WHERE p.is_available = 1 AND p.category_slug IS NOT NULL
     GROUP BY p.category_slug
     ORDER BY cnt DESC"
);

$pricesByStore = Database::query(
    "SELECT s.name as store_name, COUNT(p.id) as cnt, AVG(p.price) as avg_price, SUM(CASE WHEN p.discount_percent > 0 THEN 1 ELSE 0 END) as with_discount
     FROM stores s
     LEFT JOIN prices p ON s.id = p.store_id AND p.is_available = 1
     WHERE s.is_active = 1
     GROUP BY s.id
     ORDER BY cnt DESC"
);

$discountStats = Database::query(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN discount_percent > 0 THEN 1 ELSE 0 END) as with_discount,
        AVG(CASE WHEN discount_percent > 0 THEN discount_percent END) as avg_discount,
        MAX(discount_percent) as max_discount
     FROM prices WHERE is_available = 1"
)[0];

$recentPrices = Database::query(
    "SELECT DATE(parsed_at) as date, COUNT(*) as cnt
     FROM prices
     WHERE parsed_at > datetime('now', '-30 days')
     GROUP BY DATE(parsed_at)
     ORDER BY date DESC
     LIMIT 30"
);

$categories = Database::query("SELECT * FROM categories ORDER BY sort_order");
$categoryMap = [];
foreach ($categories as $cat) {
    $categoryMap[$cat['slug']] = $cat;
}

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Аналитика</h1>
    <p class="page-subtitle">Статистика по ценам и продуктам</p>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="card-icon cyan">📊</div>
        <div>
            <div class="stat-value"><?= number_format($discountStats['total'], 0, ',', ' ') ?></div>
            <div class="stat-label">Всего цен</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon green">🏷️</div>
        <div>
            <div class="stat-value"><?= number_format($discountStats['with_discount'], 0, ',', ' ') ?></div>
            <div class="stat-label">Со скидкой</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon purple">📉</div>
        <div>
            <div class="stat-value"><?= round($discountStats['avg_discount'] ?? 0) ?>%</div>
            <div class="stat-label">Средняя скидка</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon yellow">🔥</div>
        <div>
            <div class="stat-value"><?= round($discountStats['max_discount'] ?? 0) ?>%</div>
            <div class="stat-label">Макс. скидка</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- By Category -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📦 По категориям</h2>
        </div>

        <?php if (empty($pricesByCategory)): ?>
            <p style="color: var(--text-secondary);">Нет данных</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($pricesByCategory as $cat):
                    $catInfo = $categoryMap[$cat['category_slug']] ?? null;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 500;">
                                <?= $catInfo['emoji'] ?? '📦' ?> <?= $catInfo['name'] ?? $cat['category_slug'] ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= $cat['cnt'] ?> товаров
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: var(--font-mono); color: var(--accent-cyan);">
                                <?= formatPrice($cat['avg_price']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?= formatPrice($cat['min_price']) ?> - <?= formatPrice($cat['max_price']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- By Store -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏪 По магазинам</h2>
        </div>

        <?php if (empty($pricesByStore)): ?>
            <p style="color: var(--text-secondary);">Нет данных</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($pricesByStore as $store): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 500;"><?= $store['store_name'] ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= $store['cnt'] ?> товаров
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: var(--font-mono); color: var(--accent-cyan);">
                                <?= $store['avg_price'] ? formatPrice($store['avg_price']) : '—' ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--accent-green);">
                                <?= $store['with_discount'] ?> со скидкой
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Parsing Activity -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2 class="card-title">📈 Активность парсинга (последние 30 дней)</h2>
    </div>

    <?php if (empty($recentPrices)): ?>
        <p style="color: var(--text-secondary);">Нет данных о парсинге</p>
    <?php else: ?>
        <div style="display: flex; gap: 4px; align-items: flex-end; height: 100px; padding: 10px 0;">
            <?php
            $maxCnt = max(array_column($recentPrices, 'cnt'));
            foreach (array_reverse($recentPrices) as $day):
                $height = $maxCnt > 0 ? ($day['cnt'] / $maxCnt) * 80 : 0;
            ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                    <div style="width: 100%; background: linear-gradient(180deg, var(--accent-cyan), var(--accent-purple)); border-radius: 4px 4px 0 0; height: <?= max($height, 2) ?>px;" title="<?= $day['date'] ?>: <?= $day['cnt'] ?> товаров"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">
            <span><?= end($recentPrices)['date'] ?></span>
            <span><?= reset($recentPrices)['date'] ?></span>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
