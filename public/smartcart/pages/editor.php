<?php
$pageTitle = 'Редактор данных';
$currentPage = 'editor';

// Get data based on type
$type = $_GET['type'] ?? 'recipes';
$data = [];

if ($type === 'recipes') {
    $recipes = Database::query("SELECT * FROM recipes ORDER BY name");
    foreach ($recipes as &$recipe) {
        $recipe['ingredients'] = Database::query(
            "SELECT product_name as name, quantity, unit, is_optional, notes
             FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id",
            [$recipe['id']]
        );
        if ($recipe['instructions'] && $recipe['instructions'][0] === '[') {
            $recipe['instructions'] = json_decode($recipe['instructions'], true);
        }
        if ($recipe['tags'] && $recipe['tags'][0] === '[') {
            $recipe['tags'] = json_decode($recipe['tags'], true);
        }
    }
    $data = $recipes;
} elseif ($type === 'prices') {
    $storeSlug = $_GET['store'] ?? null;
    if ($storeSlug) {
        $store = Database::query("SELECT * FROM stores WHERE slug = ?", [$storeSlug]);
        if (!empty($store)) {
            $data = Database::query(
                "SELECT store_product_name as name, price, original_price, discount_percent as discount,
                        weight, unit, category_slug as category, url
                 FROM prices WHERE store_id = ? AND is_available = 1
                 ORDER BY category_slug, store_product_name",
                [$store[0]['id']]
            );
        }
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Редактор данных</h1>
            <p class="page-subtitle">Редактируйте JSON и сохраняйте изменения</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="?type=recipes" class="btn <?= $type === 'recipes' ? 'btn-primary' : 'btn-secondary' ?>">
                📖 Рецепты
            </a>
            <a href="?type=prices&store=perekrestok" class="btn <?= $type === 'prices' ? 'btn-primary' : 'btn-secondary' ?>">
                💰 Цены
            </a>
        </div>
    </div>
</div>

<?php if ($type === 'prices'): ?>
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <?php
        $stores = Database::query("SELECT slug, name FROM stores WHERE is_active = 1 ORDER BY name");
        $currentStore = $_GET['store'] ?? 'perekrestok';
        foreach ($stores as $store):
        ?>
        <a href="?type=prices&store=<?= $store['slug'] ?>"
           class="btn <?= $store['slug'] === $currentStore ? 'btn-primary' : 'btn-secondary' ?>"
           style="font-size: 0.85rem;">
            <?= htmlspecialchars($store['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">
            <?php if ($type === 'recipes'): ?>
                📖 Рецепты (<?= count($data) ?>)
            <?php else: ?>
                💰 Цены <?= isset($currentStore) ? htmlspecialchars($currentStore) : '' ?> (<?= count($data) ?>)
            <?php endif; ?>
        </h2>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-secondary" onclick="copyToClipboard()">
                📋 Копировать
            </button>
            <button class="btn btn-primary" onclick="saveChanges()">
                💾 Сохранить
            </button>
        </div>
    </div>

    <div style="margin-top: 16px;">
        <textarea id="jsonEditor"
                  style="width: 100%; height: 600px; font-family: var(--font-mono); font-size: 13px;
                         background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border);
                         border-radius: var(--radius-md); padding: 16px; resize: vertical;"
                  spellcheck="false"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
    </div>

    <div style="margin-top: 16px; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">
            💡 <strong>Совет:</strong> Скопируйте JSON, вставьте в Claude.ai для редактирования,
            затем вставьте обратно и нажмите "Сохранить".
        </p>
    </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
function copyToClipboard() {
    const textarea = document.getElementById('jsonEditor');
    textarea.select();
    document.execCommand('copy');
    showToast('Скопировано в буфер обмена', 'success');
}

async function saveChanges() {
    const textarea = document.getElementById('jsonEditor');
    let data;

    try {
        data = JSON.parse(textarea.value);
    } catch (e) {
        showToast('Ошибка: невалидный JSON - ' + e.message, 'error');
        return;
    }

    const type = new URLSearchParams(window.location.search).get('type') || 'recipes';
    const store = new URLSearchParams(window.location.search).get('store');

    try {
        let payload = {};

        if (type === 'recipes') {
            payload.recipes = data;
        } else if (type === 'prices' && store) {
            payload.prices = {};
            payload.prices[store] = {
                store_name: store,
                products: data
            };
        }

        const resp = await fetch(BASE_URL + '/api/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await resp.json();

        if (result.success) {
            showToast(`Сохранено! Рецептов: ${result.imported?.recipes || 0}, Цен: ${result.imported?.prices || 0}`, 'success');
        } else {
            showToast('Ошибка: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (e) {
        showToast('Ошибка: ' + e.message, 'error');
    }
}

// Format JSON on load
document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('jsonEditor');
    try {
        const data = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(data, null, 2);
    } catch (e) {}
});
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
