<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Банк навыков · Алгебра')</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js" onload="renderMathInElement(document.body,{delimiters:[{left:'$',right:'$',display:false},{left:'$$',right:'$$',display:true}],throwOnError:false})"></script>
  <style>
    :root{color-scheme:dark;--bg:#101122;--panel:#172033;--line:#304158;--text:#edf2f7;--muted:#94a3b8;--blue:#60a5fa;--green:#34d399;--yellow:#facc15;--red:#fb7185}
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--text);font:15px/1.55 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
    .wrap{max-width:1180px;margin:0 auto;padding:34px 20px 80px}
    a{color:var(--blue);text-decoration:none}
    .top{border-bottom:1px solid rgba(148,163,184,.22);padding-bottom:22px;margin-bottom:24px}
    .eyebrow{color:var(--muted);font-size:13px}
    h1{font-size:34px;line-height:1.12;margin:8px 0;letter-spacing:0}
    h2{font-size:22px;margin:0 0 14px;letter-spacing:0}
    h3{font-size:17px;margin:0;letter-spacing:0}
    .muted{color:var(--muted)}
    .stats,.chips{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .stat,.chip{border:1px solid rgba(148,163,184,.22);background:var(--panel);padding:8px 11px;border-radius:8px}
    .stat b{color:var(--blue)}
    .view-switch{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}
    .view-switch button{border:1px solid rgba(148,163,184,.3);background:#111a2a;color:var(--muted);border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer}
    .view-switch button.is-active{background:rgba(96,165,250,.16);border-color:rgba(96,165,250,.7);color:#dbeafe}
    .skill-page[data-task-view="types"] .all-view{display:none}
    .skill-page[data-task-view="all"] .types-view{display:none}
    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .skill-card,.panel,.task{background:var(--panel);border:1px solid var(--line);border-radius:8px}
    .skill-card{padding:16px;min-height:148px;display:block;color:inherit}
    .skill-card .id{color:var(--blue);font-weight:800}
    .skill-card .title{font-weight:750;font-size:18px;margin:8px 0 10px}
    .skill-card .group{color:var(--muted);font-size:13px}
    .panel{padding:18px;margin:24px 0}
    .level-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;border-bottom:1px solid rgba(148,163,184,.18);padding-bottom:12px;margin-bottom:14px}
    .level-badge{font-size:12px;font-weight:800;border-radius:999px;padding:4px 9px}
    .level-simple .level-badge{color:var(--green);background:rgba(52,211,153,.13)}
    .level-medium .level-badge{color:var(--yellow);background:rgba(250,204,21,.13)}
    .level-high .level-badge{color:var(--red);background:rgba(251,113,133,.13)}
    .taskgrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .task{position:relative;padding:14px 12px;min-height:118px}
    .flag{position:absolute;right:10px;top:10px;width:28px;height:28px;border:0;border-radius:6px;background:#263449;color:#92a0b4}
    .expr-line{display:flex;gap:10px;align-items:flex-start;min-width:0;padding-right:32px}
    .num{color:var(--blue);font-weight:800;font-size:17px}
    .expr{display:block;font-family:"KaTeX_Main","Times New Roman",serif;font-size:18px;font-weight:650;color:#f8fafc;min-width:0;max-width:100%;white-space:normal;overflow-wrap:anywhere;word-break:normal;line-height:1.35}
    .expr .katex{font-size:1.08em;white-space:normal;max-width:100%}
    .expr .katex-html{white-space:normal;overflow-wrap:anywhere}
    .expr .katex .base{display:inline;white-space:normal}
    .answer{color:#7890b2;font-size:13px;margin-top:16px}
    .answer b{color:var(--green);font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
    .status{margin-top:8px;color:var(--green);font-weight:800;font-size:11px}
    .status span{display:inline-block;width:8px;height:8px;background:var(--green);border-radius:999px;margin-right:4px}
    .group-title{margin:34px 0 14px}
    .homework-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .homework-card{border:1px solid rgba(148,163,184,.22);background:#111a2a;border-radius:8px;padding:14px}
    .homework-card h3{font-size:16px;margin-bottom:8px}
    .homework-card p{margin:0;color:var(--muted);font-size:13px}
    .homework-card details{margin-top:10px}
    .homework-card summary{cursor:pointer;color:var(--blue);font-weight:700;font-size:13px}
    .homework-card ol{margin:10px 0 0;padding-left:22px;color:#dce7f5}
    .homework-card li{margin:6px 0}
    @media(max-width:1020px){.grid,.taskgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:1020px){.homework-list{grid-template-columns:1fr}}
    @media(max-width:640px){.grid,.taskgrid{grid-template-columns:1fr}.wrap{padding-inline:14px}h1{font-size:28px}}
  </style>
  <script>
    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-view-button]');
      if (!button) return;
      const page = button.closest('[data-task-view]');
      if (!page) return;
      const view = button.getAttribute('data-view-button');
      page.setAttribute('data-task-view', view);
      page.querySelectorAll('[data-view-button]').forEach((item) => {
        item.classList.toggle('is-active', item === button);
      });
    });
  </script>
</head>
<body>@yield('body')</body>
</html>
