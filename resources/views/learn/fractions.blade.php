<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Операции с равенством - PALOMATIKA</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  :root {
    --paper: #f5efe1;
    --paper-2: #fffaf0;
    --ink: #1d2a2f;
    --muted: #64726f;
    --line: rgba(29, 42, 47, .18);
    --teal: #0f8b7f;
    --teal-dark: #0a5f5a;
    --orange: #d95f2f;
    --blue: #285f9e;
    --rose: #b83d4a;
  }
  body {
    margin: 0;
    min-height: 100vh;
    background:
      linear-gradient(135deg, rgba(15, 139, 127, .16), transparent 34%),
      linear-gradient(315deg, rgba(217, 95, 47, .14), transparent 30%),
      var(--paper);
    color: var(--ink);
    font-family: "Trebuchet MS", "Segoe UI", sans-serif;
  }
  button { font: inherit; }
  .shell {
    width: min(1240px, calc(100vw - 28px));
    margin: 0 auto;
    padding: 22px 0 28px;
  }
  .topline {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
  }
  h1 {
    max-width: 950px;
    margin: 0 0 18px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(34px, 5.5vw, 72px);
    line-height: .96;
    letter-spacing: 0;
  }
  .layout {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr);
    gap: 18px;
    align-items: stretch;
  }
  .scene {
    position: relative;
    min-height: 650px;
    border: 1px solid var(--line);
    background: #f9f5e9;
    overflow: hidden;
    box-shadow: 0 28px 76px rgba(29, 42, 47, .14);
  }
  #fractionBalanceScene {
    position: absolute;
    inset: 0;
  }
  .html-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  .formula-board,
  .status-chip,
  .pan-label {
    position: absolute;
    pointer-events: auto;
  }
  .formula-board {
    left: 24px;
    top: 22px;
    width: min(520px, calc(100% - 48px));
    padding: 14px 16px 16px;
    border: 1px solid rgba(29, 42, 47, .22);
    background: rgba(255, 250, 240, .94);
    box-shadow: 0 18px 42px rgba(29, 42, 47, .13);
  }
  .formula-board small,
  .panel-label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
  }
  .formula {
    margin-top: 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(27px, 4vw, 44px);
    line-height: 1.05;
  }
  .operation-line {
    min-height: 21px;
    margin-top: 8px;
    color: var(--teal-dark);
    font-size: 14px;
    font-weight: 800;
  }
  .status-chip {
    right: 24px;
    top: 24px;
    max-width: 260px;
    padding: 11px 12px;
    border: 1px solid rgba(15, 139, 127, .28);
    background: rgba(255, 250, 240, .88);
    color: var(--teal-dark);
    font-size: 13px;
    font-weight: 900;
    line-height: 1.3;
    box-shadow: 0 14px 34px rgba(29, 42, 47, .12);
  }
  .status-chip.is-bad {
    border-color: rgba(184, 61, 74, .34);
    color: var(--rose);
  }
  .pan-label {
    transform: translate(-50%, -50%);
    min-width: 122px;
    padding: 9px 10px;
    border: 1px solid rgba(29, 42, 47, .2);
    background: rgba(255, 250, 240, .9);
    text-align: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 20px;
    font-weight: 800;
    box-shadow: 0 14px 34px rgba(29, 42, 47, .14);
  }
  .panel {
    border: 1px solid var(--line);
    background: rgba(255, 250, 240, .9);
    padding: 20px;
    box-shadow: 0 28px 76px rgba(29, 42, 47, .1);
  }
  .panel h2 {
    margin: 7px 0 10px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 28px;
    line-height: 1.05;
    letter-spacing: 0;
  }
  .note {
    margin: 0 0 16px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.48;
  }
  .control {
    padding-top: 16px;
    margin-top: 16px;
    border-top: 1px solid var(--line);
  }
  .segmented,
  .ops {
    display: grid;
    gap: 8px;
    margin-top: 10px;
  }
  .segmented { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .ops { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .segmented button,
  .ops button,
  .reset {
    min-height: 42px;
    border: 1px solid rgba(29, 42, 47, .22);
    background: transparent;
    color: var(--ink);
    cursor: pointer;
    font-size: 13px;
    font-weight: 900;
  }
  .segmented button.is-active,
  .ops button.is-active {
    background: var(--ink);
    color: var(--paper-2);
  }
  .ops button[data-op="mul2"].is-active { background: var(--teal-dark); }
  .reset {
    width: 100%;
    margin-top: 16px;
    background: var(--orange);
    border-color: transparent;
    color: #fffaf0;
  }
  .law {
    display: grid;
    gap: 9px;
    margin-top: 16px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.4;
  }
  .law strong { color: var(--ink); }
  .history {
    display: grid;
    gap: 8px;
    margin-top: 10px;
  }
  .history div {
    padding: 10px 11px;
    border: 1px solid rgba(29, 42, 47, .14);
    background: rgba(245, 239, 225, .72);
    color: var(--ink);
    font-family: Georgia, "Times New Roman", serif;
    font-size: 17px;
  }
  @media (max-width: 940px) {
    .layout { grid-template-columns: 1fr; }
    .scene { min-height: 580px; }
    .status-chip {
      left: 24px;
      right: auto;
      top: 142px;
    }
  }
  @media (max-width: 560px) {
    .shell { width: min(100vw - 18px, 1240px); padding-top: 14px; }
    .topline { font-size: 10px; }
    .scene { min-height: 530px; }
    .formula-board { left: 12px; top: 12px; width: calc(100% - 24px); }
    .status-chip { left: 12px; top: 130px; max-width: calc(100% - 24px); }
    .panel { padding: 15px; }
    .segmented { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<main class="shell" data-fraction-balance-3d>
  <div class="topline">
    <span>PALOMATIKA</span>
    <span>3D equality lab</span>
  </div>
  <h1>Равенство выдерживает одинаковые операции</h1>

  <section class="layout" aria-label="3D объяснение операций с дробями через весы">
    <div class="scene">
      <div id="fractionBalanceScene" aria-label="3D весы с равными дробями"></div>
      <div class="html-layer">
        <section class="formula-board" aria-live="polite">
          <small>Текущее равенство</small>
          <div class="formula" id="formulaText">1/2 = 2/4</div>
          <div class="operation-line" id="operationLine">Умножаем обе части на 2</div>
        </section>
        <div class="status-chip" id="statusChip">Весы ровные: обе части изменились одинаково.</div>
        <div class="pan-label" id="leftPanLabel">1/2</div>
        <div class="pan-label" id="rightPanLabel">2/4</div>
      </div>
    </div>

    <aside class="panel">
      <span class="panel-label">Дроби · преобразования</span>
      <h2>Операция применяется ко всей части равенства</h2>
      <p class="note">Слева и справа изначально одно и то же число: `1/2` и `2/4`. Меняй действие и область применения. Если действие сделано с обеими частями, равенство остаётся верным.</p>

      <div class="control">
        <span class="panel-label">Куда применяем</span>
        <div class="segmented" role="group" aria-label="Область применения операции">
          <button type="button" data-side="both" class="is-active">Обе части</button>
          <button type="button" data-side="left">Только слева</button>
          <button type="button" data-side="right">Только справа</button>
        </div>
      </div>

      <div class="control">
        <span class="panel-label">Операция</span>
        <div class="ops" role="group" aria-label="Операции с равенством">
          <button type="button" data-op="mul2" class="is-active">× 2</button>
          <button type="button" data-op="mul3">× 3</button>
          <button type="button" data-op="div2">÷ 2</button>
          <button type="button" data-op="add16">+ 1/6</button>
          <button type="button" data-op="sub16">− 1/6</button>
          <button type="button" data-op="bad0">÷ 0</button>
        </div>
      </div>

      <div class="law" id="lawText">
        <div><strong>Можно:</strong> прибавить, вычесть, умножить или разделить обе части на одно и то же число.</div>
        <div><strong>Нельзя:</strong> делить на ноль или менять только одну часть.</div>
      </div>

      <div class="control">
        <span class="panel-label">Что произошло</span>
        <div class="history" id="historyList">
          <div>1/2 = 2/4</div>
          <div>2 · 1/2 = 2 · 2/4</div>
          <div>1 = 1</div>
        </div>
      </div>

      <button type="button" class="reset" id="resetView">Вернуть пример ×2 к обеим частям</button>
    </aside>
  </section>
</main>

<script type="module" src="/js/fraction-balance-3d.js"></script>
</body>
</html>
