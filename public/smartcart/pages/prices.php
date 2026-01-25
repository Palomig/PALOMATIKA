<?php
$pageTitle = 'Цены';
$currentPage = 'prices';

// Filters
$storeFilter = $_GET['store'] ?? null;
$categoryFilter = $_GET['category'] ?? null;
$sortBy = $_GET['sort'] ?? 'date';
$limit = 100;
$offset = (int)($_GET['offset'] ?? 0);

// Get stores and categories for filters
$stores = Database::query("SELECT * FROM stores WHERE is_active = 1 ORDER BY name");
$categories = Database::query("SELECT * FROM categories ORDER BY sort_order");

// Build query
$sql = "SELECT p.*, s.name as store_name, s.slug as store_slug
        FROM prices p
        JOIN stores s ON p.store_id = s.id
        WHERE p.is_available = 1";
$params = [];

if ($storeFilter) {
    $sql .= " AND s.slug = ?";
    $params[] = $storeFilter;
}

if ($categoryFilter) {
    $sql .= " AND p.category_slug = ?";
    $params[] = $categoryFilter;
}

// Sort
switch ($sortBy) {
    case 'price':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'discount':
        $sql .= " ORDER BY p.discount_percent DESC NULLS LAST";
        break;
    case 'name':
        $sql .= " ORDER BY p.store_product_name ASC";
        break;
    default:
        $sql .= " ORDER BY p.parsed_at DESC";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$prices = Database::query($sql, $params);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as cnt FROM prices p JOIN stores s ON p.store_id = s.id WHERE p.is_available = 1";
$countParams = [];

if ($storeFilter) {
    $countSql .= " AND s.slug = ?";
    $countParams[] = $storeFilter;
}
if ($categoryFilter) {
    $countSql .= " AND p.category_slug = ?";
    $countParams[] = $categoryFilter;
}

$totalCount = Database::query($countSql, $countParams)[0]['cnt'];

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Цены</h1>
    <p class="page-subtitle">Спарсенные цены из магазинов</p>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
        <!-- Store Filter -->
        <div>
            <label class="form-label" style="margin-bottom: 4px;">Магазин</label>
            <select class="form-select" style="width: auto;" onchange="applyFilter('store', this.value)">
                <option value="">Все магазины</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?= $store['slug'] ?>" <?= $storeFilter === $store['slug'] ? 'selected' : '' ?>>
                        <?= $store['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Category Filter -->
        <div>
            <label class="form-label" style="margin-bottom: 4px;">Категория</label>
            <select class="form-select" style="width: auto;" onchange="applyFilter('category', this.value)">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['slug'] ?>" <?= $categoryFilter === $cat['slug'] ? 'selected' : '' ?>>
                        <?= $cat['emoji'] ?> <?= $cat['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Sort -->
        <div>
            <label class="form-label" style="margin-bottom: 4px;">Сортировка</label>
            <select class="form-select" style="width: auto;" onchange="applyFilter('sort', this.value)">
                <option value="date" <?= $sortBy === 'date' ? 'selected' : '' ?>>По дате</option>
                <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>По цене</option>
                <option value="discount" <?= $sortBy === 'discount' ? 'selected' : '' ?>>По скидке</option>
                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>По названию</option>
            </select>
        </div>

        <div style="margin-left: auto; color: var(--text-secondary);">
            Найдено: <?= number_format($totalCount, 0, ',', ' ') ?>
        </div>
    </div>
</div>

<?php if (empty($prices)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        <h3>Цены не найдены</h3>
        <p>Используйте расширение для парсинга цен из магазинов</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Магазин</th>
                        <th>Цена</th>
                        <th>Скидка</th>
                        <th>Вес</th>
                        <th>Цена/кг</th>
                        <th>Обновлено</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prices as $price): ?>
                        <tr>
                            <td>
                                <div style="max-width: 300px;">
                                    <?php if ($price['url']): ?>
                                        <a href="<?= htmlspecialchars($price['url']) ?>" target="_blank">
                                            <?= htmlspecialchars($price['store_product_name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($price['store_product_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($price['category_slug']): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $price['category_slug'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $price['store_name'] ?></td>
                            <td>
                                <span style="font-family: var(--font-mono); font-weight: 600; color: var(--accent-green);">
                                    <?= formatPrice($price['price']) ?>
                                </span>
                                <?php if ($price['original_price'] && $price['original_price'] > $price['price']): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); text-decoration: line-through;">
                                        <?= formatPrice($price['original_price']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($price['discount_percent']): ?>
                                    <span class="badge badge-danger">-<?= round($price['discount_percent']) ?>%</span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-secondary);">
                                <?= $price['weight'] ?? '-' ?> <?= $price['unit'] ?? '' ?>
                            </td>
                            <td style="font-family: var(--font-mono); color: var(--text-secondary);">
                                <?php if ($price['price_per_kg']): ?>
                                    <?= formatPrice($price['price_per_kg']) ?>/кг
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= relativeTime($price['parsed_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalCount > $limit): ?>
            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                <?php if ($offset > 0): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])) ?>" class="btn btn-secondary btn-sm">
                        ← Назад
                    </a>
                <?php endif; ?>

                <span style="padding: 6px 12px; color: var(--text-secondary);">
                    <?= $offset + 1 ?>-<?= min($offset + $limit, $totalCount) ?> из <?= $totalCount ?>
                </span>

                <?php if ($offset + $limit < $totalCount): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['offset' => $offset + $limit])) ?>" class="btn btn-secondary btn-sm">
                        Далее →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$pageScripts = <<<'JS'
<script>
function applyFilter(key, value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.delete('offset'); // Reset pagination
    window.location = url;
}
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
