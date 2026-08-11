{{--
  Стили домашнего экрана ученика: приветствие, полоса Premium, баннер
  незавершённой попытки, плитки разделов.

  Вынесены из vpr-home, чтобы у ЕГЭ был тот же экран, а не собственная
  упрощённая вёрстка: раньше ОГЭ и ВПР выглядели одинаково, а ЕГЭ — как
  другой продукт (заголовок и две кнопки).

  Стек `styles` в layouts/pwa лежит ВНУТРИ <style>, поэтому здесь только
  правила, без тега.
--}}
.greeting {
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
  }
  .greeting-name { font-family: var(--display); font-size: 20px; color: var(--text); }
  .greeting-badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 6px 10px; border-radius: 999px;
    border: 1px solid var(--accent-bd); background: var(--accent-bg); color: var(--accent);
    font-size: 11px; font-weight: 800; white-space: nowrap;
  }

  .premium-strip {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 700;
    opacity: 0; animation: fadeUp 0.3s ease 0.03s forwards;
    text-decoration: none;
  }
  .premium-strip.active {
    background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple);
  }
  .premium-strip.inactive {
    background: var(--surface); border: 1px solid var(--border); color: var(--muted);
  }
  .premium-strip-dot {
    width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
  }
  .premium-strip.active .premium-strip-dot { background: var(--green); }
  .premium-strip.inactive .premium-strip-dot { background: var(--muted); }

  .resume-banner {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(59,130,246,.16), rgba(124,58,237,.12));
    border: 1.5px solid rgba(59,130,246,.35);
    border-radius: var(--r); padding: 14px 16px;
    text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.07s forwards;
  }
  .resume-banner:active { opacity: 0.85; }
  .resume-left { display: flex; align-items: center; gap: 12px; }
  .resume-pulse {
    width: 10px; height: 10px; background: var(--green);
    border-radius: 50%; flex-shrink: 0;
    animation: pulse 1.5s ease infinite;
  }
  .resume-title { font-family: var(--display); font-size: 14px; color: var(--text); }
  .resume-sub { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }
  .resume-btn {
    font-size: 12px; font-weight: 800; color: var(--accent);
    white-space: nowrap;
  }

  .tile-row { display: flex; gap: 10px; }
  .tile-big {
    flex: 1;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 18px 16px;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .tile-big:active { background: var(--surface2); }
  .tile-purple { border-color: var(--purple-bd); }
  .tile-purple:active { background: var(--purple-bg); }
  .tile-blue { border-color: var(--accent-bd); }
  .tile-blue:active { background: var(--accent-bg); }
  .tile-icon { font-size: 28px; margin-bottom: 10px; }
  .tile-name { font-family: var(--display); font-size: 15px; color: var(--text); margin-bottom: 3px; }
  .tile-desc { font-size: 11px; font-weight: 600; color: var(--muted); line-height: 1.4; }
  .tile-badge {
    display: inline-block; margin-top: 8px;
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 3px 8px; border-radius: 6px;
  }
  .badge-purple { background: var(--purple-bg); color: var(--purple); }
  .badge-blue { background: var(--accent-bg); color: var(--accent); }

  .tiles-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    opacity: 0; animation: fadeUp 0.3s ease 0.16s forwards;
  }
  .tile-sm {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px;
    cursor: pointer; transition: background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    position: relative;
  }
  .tile-sm:active { background: var(--surface2); }
  .tile-sm-icon { font-size: 22px; margin-bottom: 6px; }
  .tile-sm-name { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 2px; }
  .tile-sm-desc { font-size: 10px; font-weight: 600; color: var(--muted); line-height: 1.3; }
  .tile-badge-top-right { position: absolute; top: 8px; right: 8px; margin-top: 0; }

  .weak-section {
    opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards;
  }
  .weak-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 8px;
  }
  .weak-info { display: flex; align-items: center; gap: 10px; }
  .weak-num { font-family: var(--display); font-size: 14px; color: var(--muted); width: 24px; }
  .weak-name { font-size: 13px; font-weight: 700; color: var(--text); }
  .weak-pct { font-family: var(--display); font-size: 14px; }
  .weak-pct.low { color: var(--red); }
  .weak-pct.mid { color: var(--yellow); }
  .weak-pct.high { color: var(--green); }

  .fv-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center;
  }
  .fv-sheet {
    background: var(--bg); border-radius: 20px 20px 0 0;
    width: 100%; max-width: 420px; padding: 20px 20px 32px;
  }
  .fv-handle {
    width: 36px; height: 4px; background: var(--border);
    border-radius: 2px; margin: 0 auto 16px;
  }
  .fv-title {
    font-family: var(--display); font-size: 18px; color: var(--text);
    text-align: center; margin-bottom: 16px;
  }
  .fv-option {
    display: flex; align-items: center; gap: 14px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 16px;
    margin-bottom: 10px; cursor: pointer; transition: border-color 0.15s;
    text-decoration: none; color: inherit;
  }
  .fv-option:active { background: var(--surface2); }
  .fv-opt-icon { font-size: 28px; flex-shrink: 0; }
  .fv-opt-name { font-family: var(--display); font-size: 15px; color: var(--text); }
  .fv-opt-desc { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; line-height: 1.3; }
  .fv-cancel {
    display: block; width: 100%; padding: 14px;
    background: none; border: none; color: var(--muted);
    font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 4px;
  }

  @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
