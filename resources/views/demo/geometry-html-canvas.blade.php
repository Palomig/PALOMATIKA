<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>HTML-in-Canvas demo - Palomatika</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  :root {
    --ink: #1f2a2e;
    --paper: #f7f2e8;
    --paper-deep: #ece2d1;
    --line: #274c5b;
    --accent: #d94f30;
    --accent-2: #147c72;
    --muted: #6c7775;
    --shadow: 0 24px 70px rgba(31, 42, 46, 0.18);
  }
  body {
    margin: 0;
    min-height: 100vh;
    background:
      linear-gradient(90deg, rgba(31,42,46,0.05) 1px, transparent 1px) 0 0 / 32px 32px,
      linear-gradient(rgba(31,42,46,0.045) 1px, transparent 1px) 0 0 / 32px 32px,
      var(--paper);
    color: var(--ink);
    font-family: Georgia, "Times New Roman", serif;
  }
  button, input { font: inherit; }
  .demo-shell {
    width: min(1180px, calc(100vw - 32px));
    margin: 0 auto;
    padding: 28px 0;
  }
  .demo-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
    color: var(--muted);
    font: 700 12px/1.2 ui-sans-serif, system-ui, sans-serif;
    letter-spacing: .12em;
    text-transform: uppercase;
  }
  .demo-title {
    margin: 0 0 18px;
    max-width: 860px;
    font-size: clamp(36px, 6vw, 76px);
    line-height: .92;
    letter-spacing: 0;
  }
  .demo-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr);
    gap: 18px;
    align-items: stretch;
  }
  .geometry-stage {
    position: relative;
    min-height: 620px;
    border: 2px solid rgba(31, 42, 46, .2);
    background:
      radial-gradient(circle at 20% 10%, rgba(217, 79, 48, .16), transparent 36%),
      linear-gradient(135deg, #fffaf0, #efe3cd);
    box-shadow: var(--shadow);
    overflow: hidden;
  }
  .geometry-canvas {
    display: block;
    width: 100%;
    height: 620px;
  }
  .html-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  .point-label,
  .angle-card,
  .arc-chip,
  .drag-handle {
    position: absolute;
    pointer-events: auto;
    transform: translate(-50%, -50%);
  }
  .point-label {
    min-width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(31,42,46,.32);
    border-radius: 50%;
    background: rgba(255, 250, 240, .94);
    color: var(--line);
    font: 800 16px/1 ui-sans-serif, system-ui, sans-serif;
    box-shadow: 0 8px 18px rgba(31, 42, 46, .12);
  }
  .drag-handle {
    width: 46px;
    height: 46px;
    border: 2px solid rgba(217, 79, 48, .4);
    border-radius: 50%;
    background: rgba(217, 79, 48, .12);
    cursor: grab;
  }
  .drag-handle:active { cursor: grabbing; }
  .angle-card {
    width: min(260px, calc(100vw - 80px));
    padding: 12px;
    border: 1px solid rgba(31,42,46,.22);
    background: rgba(255, 250, 240, .94);
    box-shadow: 0 16px 40px rgba(31,42,46,.16);
  }
  .angle-card label {
    display: block;
    color: var(--muted);
    font: 700 11px/1.2 ui-sans-serif, system-ui, sans-serif;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .answer-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
  }
  .answer-row input {
    width: 86px;
    border: 1px solid rgba(31,42,46,.32);
    background: #fffdf6;
    color: var(--ink);
    padding: 8px 10px;
    font: 800 20px/1 ui-sans-serif, system-ui, sans-serif;
  }
  .answer-row button,
  .side-panel button {
    border: 0;
    background: var(--ink);
    color: #fffaf0;
    padding: 10px 12px;
    cursor: pointer;
    font: 800 12px/1 ui-sans-serif, system-ui, sans-serif;
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .feedback {
    min-height: 18px;
    margin-top: 8px;
    color: var(--accent-2);
    font: 700 13px/1.35 ui-sans-serif, system-ui, sans-serif;
  }
  .arc-chip {
    border: 1px solid rgba(20, 124, 114, .28);
    background: rgba(20, 124, 114, .1);
    color: var(--accent-2);
    padding: 8px 10px;
    font: 800 13px/1 ui-sans-serif, system-ui, sans-serif;
  }
  .side-panel {
    border: 2px solid rgba(31,42,46,.2);
    background: rgba(255, 250, 240, .78);
    padding: 22px;
    box-shadow: var(--shadow);
  }
  .task-kicker {
    margin: 0 0 12px;
    color: var(--accent);
    font: 800 12px/1.2 ui-sans-serif, system-ui, sans-serif;
    letter-spacing: .12em;
    text-transform: uppercase;
  }
  .task-text {
    margin: 0 0 18px;
    font-size: 26px;
    line-height: 1.18;
  }
  .step-list {
    display: grid;
    gap: 10px;
    margin: 20px 0;
  }
  .step {
    border-top: 1px solid rgba(31,42,46,.18);
    padding-top: 10px;
    color: var(--muted);
    font: 600 14px/1.45 ui-sans-serif, system-ui, sans-serif;
  }
  .step strong {
    color: var(--ink);
    display: block;
    margin-bottom: 2px;
  }
  .control {
    display: grid;
    gap: 8px;
    margin-top: 18px;
  }
  .control label {
    color: var(--muted);
    font: 800 12px/1.2 ui-sans-serif, system-ui, sans-serif;
    text-transform: uppercase;
    letter-spacing: .08em;
  }
  input[type="range"] { width: 100%; accent-color: var(--accent); }
  .mode-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 16px;
  }
  .mode-row button {
    background: transparent;
    color: var(--ink);
    border: 1px solid rgba(31,42,46,.25);
  }
  .mode-row button.is-active {
    background: var(--ink);
    color: #fffaf0;
  }
  @media (max-width: 860px) {
    .demo-layout { grid-template-columns: 1fr; }
    .geometry-stage { min-height: 520px; }
    .geometry-canvas { height: 520px; }
    .task-text { font-size: 22px; }
  }
</style>
</head>
<body>
<main class="demo-shell" data-geometry-html-canvas-demo>
  <div class="demo-topbar">
    <span>Palomatika</span>
    <span>HTML-in-Canvas</span>
  </div>

  <h1 class="demo-title">Задание 16 как живая геометрическая сцена</h1>

  <section class="demo-layout" aria-label="Интерактивное задание 16">
    <div class="geometry-stage" id="geometryStage">
      <canvas class="geometry-canvas" id="geometryCanvas" layoutsubtree aria-label="Окружность с хордой AB и вписанным углом ADB"></canvas>
      <div class="html-layer" id="htmlLayer">
        <button class="drag-handle" id="dragHandle" type="button" aria-label="Переместить точку D"></button>
        <div class="point-label" data-point="A">A</div>
        <div class="point-label" data-point="B">B</div>
        <div class="point-label" data-point="D">D</div>
        <div class="arc-chip" id="arcChip">дуга AB = 120°</div>
        <form class="angle-card" id="answerCard">
          <label for="angleAnswer">Ответ: угол ADB</label>
          <div class="answer-row">
            <input id="angleAnswer" type="number" inputmode="numeric" min="0" max="180" placeholder="?" aria-label="Ответ в градусах">
            <span>°</span>
            <button type="submit">OK</button>
          </div>
          <div class="feedback" id="feedback"></div>
        </form>
      </div>
    </div>

    <aside class="side-panel">
      <p class="task-kicker">ОГЭ · номер 16</p>
      <p class="task-text">На окружности отмечены точки A, B и D. Меньшая дуга AB равна 120°. Найдите угол ADB.</p>

      <div class="step-list">
        <div class="step"><strong>1.</strong> Угол ADB вписанный, он опирается на дугу AB.</div>
        <div class="step"><strong>2.</strong> Вписанный угол равен половине дуги, на которую он опирается.</div>
        <div class="step"><strong>3.</strong> При движении точки D ответ не меняется: 120° / 2 = 60°.</div>
      </div>

      <div class="control">
        <label for="pointSlider">Положение точки D</label>
        <input id="pointSlider" type="range" min="205" max="335" value="266">
      </div>

      <div class="mode-row" aria-label="Режим показа">
        <button type="button" data-mode="task" class="is-active">Задача</button>
        <button type="button" data-mode="hint">Подсказка</button>
        <button type="button" data-mode="answer">Решение</button>
      </div>
    </aside>
  </section>
</main>

<script src="/js/geometry-html-canvas-demo.js"></script>
</body>
</html>
