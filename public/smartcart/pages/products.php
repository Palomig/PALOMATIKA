<?php
$pageTitle = 'Продукты';
$currentPage = 'products';

// Get categories
$categories = Database::query("SELECT * FROM categories ORDER BY sort_order");

// Get products with best prices
$categoryFilter = $_GET['category'] ?? null;

$sql = "SELECT p.*, c.name as category_name, c.emoji as category_emoji, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id";
$params = [];

if ($categoryFilter) {
    $sql .= " WHERE c.slug = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY c.sort_order, p.name";

$products = Database::query($sql, $params);

// Add best price for each product
foreach ($products as &$product) {
    $bestPrice = Database::query(
        "SELECT pr.price, pr.original_price, pr.discount_percent, s.name as store_name
         FROM prices pr
         JOIN stores s ON pr.store_id = s.id
         WHERE pr.product_id = ? AND pr.is_available = 1
         ORDER BY pr.price ASC LIMIT 1",
        [$product['id']]
    );
    $product['best_price'] = $bestPrice[0]['price'] ?? null;
    $product['original_price'] = $bestPrice[0]['original_price'] ?? null;
    $product['discount'] = $bestPrice[0]['discount_percent'] ?? null;
    $product['best_store'] = $bestPrice[0]['store_name'] ?? null;
}

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Продукты</h1>
    <p class="page-subtitle">База продуктов с ценами из магазинов</p>
</div>

<!-- Category Pills -->
<div class="category-pills">
    <a href="<?= BASE_URL ?>/products" class="category-pill <?= !$categoryFilter ? 'active' : '' ?>">
        Все
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/products?category=<?= $cat['slug'] ?>" class="category-pill <?= $categoryFilter === $cat['slug'] ? 'active' : '' ?>">
            <span class="category-emoji"><?= $cat['emoji'] ?></span>
            <?= $cat['name'] ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <h3>Продуктов нет</h3>
        <p>База продуктов пуста</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Продукт</th>
                        <th>Категория</th>
                        <th>Вес</th>
                        <th>Лучшая цена</th>
                        <th>Магазин</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $currentCategory = null;
                    foreach ($products as $product):
                        // Category header
                        if ($product['category_name'] !== $currentCategory):
                            $currentCategory = $product['category_name'];
                    ?>
                        <tr style="background: var(--bg-tertiary);">
                            <td colspan="6" style="font-weight: 600; color: var(--accent-cyan);">
                                <?= $product['category_emoji'] ?? '' ?> <?= $product['category_name'] ?? 'Без категории' ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/products/<?= $product['id'] ?>" style="font-weight: 500;">
                                <?= htmlspecialchars($product['name']) ?>
                            </a>
                        </td>
                        <td style="color: var(--text-secondary);">
                            <?= $product['category_emoji'] ?? '' ?>
                        </td>
                        <td style="color: var(--text-secondary);">
                            <?= $product['default_weight'] ?? '-' ?> <?= $product['default_unit'] ?? '' ?>
                        </td>
                        <td>
                            <?php if ($product['best_price']): ?>
                                <span style="font-family: var(--font-mono); color: var(--accent-green);">
                                    <?= formatPrice($product['best_price']) ?>
                                </span>
                                <?php if ($product['discount']): ?>
                                    <span class="badge badge-danger" style="margin-left: 8px;">-<?= round($product['discount']) ?>%</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-secondary);">
                            <?= $product['best_store'] ?? '—' ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick="addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>')">
                                🛒
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$pageScripts = <<<'JS'
<script>
async function addToCart(productId, productName) {
    try {
        const response = await fetch(BASE_URL + '/api/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                name: productName
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(productName + ' добавлен в корзину', 'success');
        }
    } catch (e) {
        showToast('Ошибка добавления', 'error');
    }
}
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
