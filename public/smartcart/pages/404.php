<?php
$pageTitle = '404 - Страница не найдена';
$currentPage = '';
require __DIR__ . '/../templates/header.php';
?>

<div class="empty-state" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 100px; height: 100px;">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>

    <h1 style="font-size: 4rem; font-family: var(--font-mono); color: var(--accent-cyan); margin: 20px 0;">404</h1>

    <h3>Страница не найдена</h3>
    <p style="margin-bottom: 30px;">Возможно, страница была удалена или вы ввели неверный адрес</p>

    <a href="<?= BASE_URL ?>/" class="btn btn-primary">
        ← Вернуться на главную
    </a>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
