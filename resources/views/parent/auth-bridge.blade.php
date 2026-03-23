<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Вход...</title>
  <style>
    body{margin:0;background:#f0f4fb;color:#1a2340;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
    .card{max-width:360px;width:100%;background:#fff;border:1px solid #dde3f0;border-radius:16px;padding:18px;text-align:center}
    .spin{width:20px;height:20px;border:2px solid #c7d3ee;border-top-color:#3b6ef8;border-radius:50%;display:inline-block;animation:r 1s linear infinite;margin-bottom:10px}
    .btn{display:inline-block;margin-top:12px;padding:10px 14px;border-radius:10px;background:#3b6ef8;color:#fff;text-decoration:none;font-weight:700}
    @keyframes r{to{transform:rotate(360deg)}}
  </style>
</head>
<body>
  <div class="card">
    <div class="spin"></div>
    <div>Входим в приложение для родителей…</div>
    <a id="go" class="btn" href="{{ $redirectTo }}">Продолжить</a>
  </div>
  <script>
    const finalUrl = @json($redirectTo);
    const handoffToken = @json($handoffToken ?? '');
    const continueUrl = '/parent/auth/continue?token=' + encodeURIComponent(handoffToken) + '&next=' + encodeURIComponent(finalUrl);
    setTimeout(() => { window.location.replace(continueUrl); }, 220);
  </script>
</body>
</html>
