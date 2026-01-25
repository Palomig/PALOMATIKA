<?php
$pageTitle = 'Рецепты';
$currentPage = 'recipes';

// Filters
$categoryFilter = $_GET['category'] ?? null;
$equipmentFilter = $_GET['equipment'] ?? null;

// Build query
$sql = "SELECT * FROM recipes WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}
if ($equipmentFilter) {
    $sql .= " AND equipment LIKE ?";
    $params[] = "%{$equipmentFilter}%";
}

$sql .= " ORDER BY is_favorite DESC, times_cooked DESC, name";

$recipes = Database::query($sql, $params);

// Get unique categories and equipment for filters
$categories = Database::query("SELECT DISTINCT category FROM recipes WHERE category IS NOT NULL ORDER BY category");
$equipments = Database::query("SELECT DISTINCT equipment FROM recipes WHERE equipment IS NOT NULL ORDER BY equipment");

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Рецепты</h1>
    <p class="page-subtitle">Коллекция рецептов с расчётом стоимости</p>
</div>

<!-- Filters -->
<div class="filters">
    <a href="<?= BASE_URL ?>/recipes" class="filter-btn <?= !$categoryFilter && !$equipmentFilter ? 'active' : '' ?>">Все</a>

    <?php foreach ($categories as $cat): ?>
        <?php if ($cat['category']): ?>
            <a href="<?= BASE_URL ?>/recipes?category=<?= urlencode($cat['category']) ?>" class="filter-btn <?= $categoryFilter === $cat['category'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['category']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

    <span style="color: var(--text-muted);">|</span>

    <?php foreach ($equipments as $eq): ?>
        <?php if ($eq['equipment']): ?>
            <a href="<?= BASE_URL ?>/recipes?equipment=<?= urlencode($eq['equipment']) ?>" class="filter-btn <?= $equipmentFilter === $eq['equipment'] ? 'active' : '' ?>">
                <?= htmlspecialchars($eq['equipment']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if (empty($recipes)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <path d="M14 2v6h6"/>
        </svg>
        <h3>Рецептов пока нет</h3>
        <p>Импортируйте рецепты через настройки или добавьте вручную</p>
        <a href="<?= BASE_URL ?>/settings" class="btn btn-primary" style="margin-top: 20px;">
            Импортировать рецепты
        </a>
    </div>
<?php else: ?>
    <div class="recipes-grid">
        <?php foreach ($recipes as $recipe):
            $cost = calculateRecipeCost($recipe['id']);
            $ingCount = Database::query("SELECT COUNT(*) as cnt FROM recipe_ingredients WHERE recipe_id = ?", [$recipe['id']])[0]['cnt'];
        ?>
            <a href="<?= BASE_URL ?>/recipes/<?= $recipe['id'] ?>" class="recipe-card" style="text-decoration: none; position: relative;">
                <?php if ($recipe['is_favorite']): ?>
                    <div style="position: absolute; top: 10px; right: 10px; z-index: 1;">⭐</div>
                <?php endif; ?>

                <div class="recipe-image">
                    <?php if ($recipe['image_svg']): ?>
                        <?= $recipe['image_svg'] ?>
                    <?php else: ?>
                        <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--accent-cyan); opacity: 0.3;">
                            <circle cx="50" cy="35" r="20"/>
                            <path d="M25 80 Q50 55 75 80" stroke-width="2"/>
                        </svg>
                    <?php endif; ?>
                </div>

                <div class="recipe-content">
                    <h3 class="recipe-name"><?= htmlspecialchars($recipe['name']) ?></h3>

                    <div class="recipe-meta">
                        <?php if ($recipe['cook_time']): ?>
                            <span>⏱ <?= $recipe['cook_time'] ?> мин</span>
                        <?php endif; ?>
                        <span>📦 <?= $ingCount ?> ингр.</span>
                        <?php if ($recipe['servings']): ?>
                            <span>👥 <?= $recipe['servings'] ?> порц.</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($recipe['equipment']): ?>
                        <div style="margin-bottom: 10px;">
                            <span class="badge badge-info"><?= htmlspecialchars($recipe['equipment']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="recipe-price">
                        <span class="price-value">от <?= formatPrice($cost['total']) ?></span>
                        <?php if (!$cost['complete']): ?>
                            <span class="badge badge-warning" title="Не все ингредиенты в базе">⚠</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
