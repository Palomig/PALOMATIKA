<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Palomatika для родителей</title>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
<script src="/js/telegram-web-app.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Nunito', sans-serif;
    background: linear-gradient(135deg, #e8efff 0%, #f5f0ff 100%);
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 24px;
  }
  .logo { font-family: 'Russo One', sans-serif; font-size: 28px; color: #3b6ef8; margin-bottom: 8px; text-align: center; }
  .tagline { font-size: 14px; color: #6b7a9a; text-align: center; margin-bottom: 40px; line-height: 1.5; }
  .card { background: #fff; border: 1px solid #dde3f0; border-radius: 20px; padding: 28px 24px; max-width: 380px; width: 100%; text-align: center; }
  .card-title { font-family: 'Russo One', sans-serif; font-size: 20px; color: #1a2340; margin-bottom: 8px; }
  .card-sub { font-size: 13px; color: #6b7a9a; margin-bottom: 24px; line-height: 1.5; }
  .btn-tg {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 14px; background: #3b6ef8; color: #fff;
    border: none; border-radius: 14px; font-size: 16px; font-weight: 700;
    cursor: pointer; text-decoration: none;
  }
  .error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px; color: #dc2626; font-size: 13px; margin-bottom: 16px; }
  .features { display: flex; flex-direction: column; gap: 10px; margin-top: 28px; }
  .feature { display: flex; align-items: center; gap: 12px; text-align: left; }
  .feature-icon { font-size: 22px; }
  .feature-text { font-size: 13px; color: #4b5568; line-height: 1.4; }
  .feature-text strong { color: #1a2340; }
</style>
</head>
<body>

<div class="logo">palomatika</div>
<div class="tagline">Следите за успехами ребёнка<br>в подготовке к ОГЭ</div>

<div class="card">
  @if(session('error'))
  <div class="error">{{ session('error') }}</div>
  @endif

  <div class="card-title">Войти через Telegram</div>
  <div class="card-sub">Открывайте через Telegram Mini App<br>@palomatika_parent_bot</div>

  <form id="auth-form" method="POST" action="{{ route('parent.auth') }}">
    @csrf
    <input type="hidden" name="initData" id="init-data">
    <button type="button" class="btn-tg" onclick="doAuth()">
      <span>✈️</span> Войти через Telegram
    </button>
  </form>

  <div class="features">
    <div class="feature">
      <span class="feature-icon">📊</span>
      <div class="feature-text"><strong>Дерево навыков</strong> — видите, что ребёнок знает и что нужно подтянуть</div>
    </div>
    <div class="feature">
      <span class="feature-icon">📅</span>
      <div class="feature-text"><strong>Посещаемость</strong> — история уроков с отметками присутствия</div>
    </div>
    <div class="feature">
      <span class="feature-icon">📝</span>
      <div class="feature-text"><strong>Домашние задания</strong> — статус выполнения ДЗ</div>
    </div>
  </div>
</div>

<script>
function doAuth() {
  const tg = window.Telegram?.WebApp;
  const initData = tg?.initData ?? '';
  if (!initData) {
    alert('Откройте через Telegram Mini App (@palomatika_parent_bot)');
    return;
  }
  document.getElementById('init-data').value = initData;
  document.getElementById('auth-form').submit();
}

// Авто-вход если открыто в Telegram
window.addEventListener('DOMContentLoaded', () => {
  const tg = window.Telegram?.WebApp;
  if (tg?.initData && tg.initData !== '') {
    document.getElementById('init-data').value = tg.initData;
    document.getElementById('auth-form').submit();
  }
});
</script>

</body>
</html>
