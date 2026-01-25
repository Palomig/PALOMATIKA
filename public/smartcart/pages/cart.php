<?php
$pageTitle = 'Корзина';
$currentPage = 'cart';

// Get cart items
$cartItems = Database::query(
    "SELECT * FROM shopping_list ORDER BY is_checked ASC, added_at DESC"
);

// Compare stores
$comparison = [];
if (!empty($cartItems)) {
    $comparison = compareStoresForCart($cartItems);
}

$checkedCount = count(array_filter($cartItems, fn($i) => $i['is_checked']));

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title">Корзина</h1>
        <p class="page-subtitle">
            <?php if (count($cartItems) > 0): ?>
                <?= $checkedCount ?>/<?= count($cartItems) ?> товаров отмечено
            <?php else: ?>
                Список покупок пуст
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($cartItems)): ?>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-secondary" onclick="clearCart(false)">
                Убрать отмеченные
            </button>
            <button class="btn btn-danger" onclick="clearCart(true)">
                Очистить всё
            </button>
        </div>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Shopping List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📝 Список покупок</h2>
            <button class="btn btn-sm btn-primary" onclick="showAddModal()">+ Добавить</button>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <h3>Корзина пуста</h3>
                <p>Добавьте товары из рецептов или вручную</p>
            </div>
        <?php else: ?>
            <div id="cartList">
                <?php foreach ($cartItems as $item): ?>
                    <div class="shopping-item <?= $item['is_checked'] ? 'checked' : '' ?>" data-id="<?= $item['id'] ?>">
                        <input type="checkbox" class="checkbox"
                               <?= $item['is_checked'] ? 'checked' : '' ?>
                               onchange="toggleItem(<?= $item['id'] ?>)">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <?php if ($item['quantity'] > 1): ?>
                            <span style="color: var(--text-secondary);">×<?= $item['quantity'] ?></span>
                        <?php endif; ?>
                        <?php if ($item['expected_price']): ?>
                            <span class="item-price"><?= formatPrice($item['expected_price']) ?></span>
                        <?php endif; ?>
                        <button class="btn btn-icon btn-secondary btn-sm" onclick="removeItem(<?= $item['id'] ?>)">
                            ×
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Store Comparison -->
    <div>
        <?php if (!empty($comparison)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">💰 Сравнение магазинов</h2>
                </div>

                <div class="store-compare">
                    <?php foreach ($comparison as $i => $sc): ?>
                        <div class="store-row <?= $i === 0 && $sc['total'] > 0 ? 'best' : '' ?>">
                            <div>
                                <div class="store-name"><?= $sc['store']['name'] ?></div>
                                <div class="store-delivery"><?= $sc['delivery_time'] ?></div>
                                <?php if (!empty($sc['missing'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--accent-yellow); margin-top: 4px;">
                                        нет <?= count($sc['missing']) ?> товаров
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <div class="store-total">
                                    <?php if ($sc['total'] > 0): ?>
                                        <?= formatPrice($sc['total']) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?= $sc['available'] ?>/<?= count($cartItems) ?> товаров
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-secondary);">
                    💡 Выберите магазин в расширении и нажмите "Начать покупки"
                </div>
            </div>
        <?php endif; ?>

        <!-- Add from Recipe -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2 class="card-title">📖 Добавить из рецепта</h2>
            </div>

            <?php
            $recipes = Database::query("SELECT id, name FROM recipes ORDER BY name LIMIT 10");
            ?>

            <?php if (!empty($recipes)): ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($recipes as $recipe): ?>
                        <button class="btn btn-secondary" style="justify-content: flex-start;" onclick="addRecipeToCart(<?= $recipe['id'] ?>)">
                            + <?= htmlspecialchars($recipe['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-secondary);">Рецепты не найдены</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%;">
        <div class="card-header">
            <h2 class="card-title">Добавить товар</h2>
            <button class="btn btn-icon btn-secondary" onclick="hideAddModal()">×</button>
        </div>

        <form onsubmit="addItem(event)">
            <div class="form-group">
                <label class="form-label">Название</label>
                <input type="text" class="form-input" id="itemName" required placeholder="Например: Молоко">
            </div>
            <div class="form-group">
                <label class="form-label">Количество</label>
                <input type="number" class="form-input" id="itemQty" value="1" min="1">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Добавить</button>
        </form>
    </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
async function toggleItem(id) {
    try {
        await fetch(BASE_URL + '/api/cart/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const item = document.querySelector(`[data-id="${id}"]`);
        item.classList.toggle('checked');
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

async function removeItem(id) {
    try {
        await fetch(BASE_URL + '/api/cart/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        document.querySelector(`[data-id="${id}"]`).remove();
        showToast('Товар удалён', 'success');
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

async function clearCart(all) {
    if (!confirm(all ? 'Очистить всю корзину?' : 'Удалить отмеченные товары?')) return;

    try {
        await fetch(BASE_URL + '/api/cart/clear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ only_checked: !all })
        });

        location.reload();
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    document.getElementById('itemName').focus();
}

function hideAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

async function addItem(e) {
    e.preventDefault();

    const name = document.getElementById('itemName').value;
    const quantity = document.getElementById('itemQty').value;

    try {
        await fetch(BASE_URL + '/api/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, quantity })
        });

        location.reload();
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

async function addRecipeToCart(recipeId) {
    try {
        const response = await fetch(BASE_URL + '/api/recipes/' + recipeId);
        const data = await response.json();

        const ingredients = data.ingredients.map(i => ({
            name: i.product_name,
            quantity: i.quantity,
            unit: i.unit
        }));

        await fetch(BASE_URL + '/api/cart/bulk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: ingredients })
        });

        location.reload();
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

// Close modal on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') hideAddModal();
});
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
