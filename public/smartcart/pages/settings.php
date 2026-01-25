<?php
$pageTitle = 'Настройки';
$currentPage = 'settings';

// Handle import
$importMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if ($data) {
            // Process import via API
            $ch = curl_init(BASE_URL . '/api/import');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            if ($result && $result['success']) {
                $importMessage = [
                    'type' => 'success',
                    'text' => "Импортировано: {$result['imported']['recipes']} рецептов, {$result['imported']['products']} продуктов, {$result['imported']['prices']} цен"
                ];
            } else {
                $importMessage = ['type' => 'error', 'text' => 'Ошибка импорта'];
            }
        } else {
            $importMessage = ['type' => 'error', 'text' => 'Неверный формат файла'];
        }
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Настройки</h1>
    <p class="page-subtitle">Управление данными и экспорт</p>
</div>

<?php if ($importMessage): ?>
    <div class="toast <?= $importMessage['type'] ?>" style="position: static; margin-bottom: 20px;">
        <?= $importMessage['text'] ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Export -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📤 Экспорт данных</h2>
        </div>

        <p style="color: var(--text-secondary); margin-bottom: 20px;">
            Скачайте данные в формате JSON для редактирования или резервного копирования.
        </p>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="<?= BASE_URL ?>/api/export/all" class="btn btn-primary" download>
                📦 Экспорт всех данных
            </a>
            <a href="<?= BASE_URL ?>/api/export/recipes" class="btn btn-secondary" download>
                📖 Только рецепты
            </a>
            <a href="<?= BASE_URL ?>/api/export/prices" class="btn btn-secondary" download>
                💰 Только цены
            </a>
            <a href="<?= BASE_URL ?>/api/export/products" class="btn btn-secondary" download>
                📦 Только продукты
            </a>
        </div>
    </div>

    <!-- Import -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📥 Импорт данных</h2>
        </div>

        <p style="color: var(--text-secondary); margin-bottom: 20px;">
            Загрузите JSON файл с данными. Существующие записи будут обновлены.
        </p>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Выберите JSON файл</label>
                <input type="file" name="import_file" accept=".json" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Импортировать
            </button>
        </form>
    </div>
</div>

<!-- Database Stats -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2 class="card-title">📊 Статистика базы данных</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
        <div style="text-align: center; padding: 20px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
            <div style="font-size: 2rem; font-family: var(--font-mono); color: var(--accent-cyan);"><?= $stats['products'] ?></div>
            <div style="color: var(--text-secondary);">Продуктов</div>
        </div>
        <div style="text-align: center; padding: 20px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
            <div style="font-size: 2rem; font-family: var(--font-mono); color: var(--accent-purple);"><?= $stats['prices'] ?></div>
            <div style="color: var(--text-secondary);">Цен</div>
        </div>
        <div style="text-align: center; padding: 20px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
            <div style="font-size: 2rem; font-family: var(--font-mono); color: var(--accent-green);"><?= $stats['recipes'] ?></div>
            <div style="color: var(--text-secondary);">Рецептов</div>
        </div>
        <div style="text-align: center; padding: 20px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
            <div style="font-size: 2rem; font-family: var(--font-mono); color: var(--accent-yellow);"><?= $stats['cart_items'] ?></div>
            <div style="color: var(--text-secondary);">В корзине</div>
        </div>
    </div>
</div>

<!-- Danger Zone -->
<div class="card" style="margin-top: 24px; border-color: var(--accent-red);">
    <div class="card-header">
        <h2 class="card-title" style="color: var(--accent-red);">⚠️ Опасная зона</h2>
    </div>

    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Эти действия нельзя отменить. Будьте осторожны!
    </p>

    <div style="display: flex; gap: 12px;">
        <button class="btn btn-danger" onclick="clearPrices()">
            🗑️ Очистить все цены
        </button>
        <button class="btn btn-danger" onclick="clearCart()">
            🛒 Очистить корзину
        </button>
        <button class="btn btn-danger" onclick="resetDatabase()">
            💣 Сбросить базу данных
        </button>
    </div>
</div>

<!-- API Info -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2 class="card-title">🔌 API для расширения</h2>
    </div>

    <p style="color: var(--text-secondary); margin-bottom: 16px;">
        URL сервера для настройки расширения:
    </p>

    <div style="background: var(--bg-tertiary); padding: 16px; border-radius: var(--radius-md); font-family: var(--font-mono); word-break: break-all;">
        <?= BASE_URL ?>
    </div>

    <div style="margin-top: 20px;">
        <h3 style="font-size: 1rem; margin-bottom: 12px;">Доступные endpoints:</h3>
        <ul style="color: var(--text-secondary); padding-left: 20px;">
            <li><code>GET /api/stores</code> — список магазинов</li>
            <li><code>GET /api/cart</code> — текущая корзина</li>
            <li><code>POST /api/prices/bulk</code> — массовый импорт цен</li>
            <li><code>GET /api/export/all</code> — экспорт всех данных</li>
            <li><code>POST /api/import</code> — импорт данных</li>
        </ul>
    </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
async function clearPrices() {
    if (!confirm('Удалить все спарсенные цены? Это действие нельзя отменить!')) return;

    try {
        // TODO: Implement API endpoint
        showToast('Функция в разработке', 'error');
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

async function clearCart() {
    if (!confirm('Очистить корзину?')) return;

    try {
        await fetch(BASE_URL + '/api/cart/clear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        showToast('Корзина очищена', 'success');
    } catch (e) {
        showToast('Ошибка', 'error');
    }
}

async function resetDatabase() {
    if (!confirm('ВНИМАНИЕ! Это удалит ВСЕ данные: продукты, цены, рецепты, корзину. Продолжить?')) return;
    if (!confirm('Вы уверены? Это действие НЕЛЬЗЯ отменить!')) return;

    showToast('Функция отключена для безопасности', 'error');
}
</script>
JS;

require __DIR__ . '/../templates/footer.php';
?>
