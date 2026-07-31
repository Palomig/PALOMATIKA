<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>3D-разбор геометрии - Palomatika</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  :root {
    --bg: #eef3f0;
    --ink: #172124;
    --muted: #61706d;
    --panel: rgba(255, 255, 250, .9);
    --line: rgba(23, 33, 36, .18);
    --orange: #d85a2a;
    --teal: #0f8b7f;
    --blue: #245f9f;
  }
  body {
    margin: 0;
    min-height: 100vh;
    background:
      linear-gradient(120deg, rgba(15,139,127,.14), transparent 42%),
      linear-gradient(300deg, rgba(216,90,42,.12), transparent 38%),
      var(--bg);
    color: var(--ink);
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }
  button, input { font: inherit; }
  .shell {
    width: min(1220px, calc(100vw - 28px));
    margin: 0 auto;
    padding: 24px 0;
  }
  .topline {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
  }
  h1 {
    margin: 0 0 18px;
    max-width: 900px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(38px, 6vw, 78px);
    line-height: .96;
    letter-spacing: 0;
  }
  .layout {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(310px, .55fr);
    gap: 18px;
    align-items: stretch;
  }
  .scene-card {
    position: relative;
    min-height: 660px;
    border: 1px solid var(--line);
    background: #f8faf5;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(23, 33, 36, .16);
  }
  #threeScene {
    position: absolute;
    inset: 0;
  }
  .dom-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  .label3d,
  .formula-card,
  .angle-chip {
    position: absolute;
    pointer-events: auto;
    transform: translate(-50%, -50%);
  }
  .label3d {
    min-width: 34px;
    min-height: 34px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(23, 33, 36, .24);
    border-radius: 999px;
    background: rgba(255, 255, 250, .94);
    color: var(--blue);
    font-weight: 900;
    box-shadow: 0 10px 24px rgba(23, 33, 36, .16);
  }
  .angle-chip {
    padding: 8px 10px;
    border: 1px solid rgba(216,90,42,.28);
    background: rgba(255,255,250,.9);
    color: var(--orange);
    font-size: 13px;
    font-weight: 900;
    box-shadow: 0 12px 30px rgba(23, 33, 36, .12);
  }
  .formula-card {
    width: min(280px, calc(100vw - 70px));
    padding: 14px;
    border: 1px solid rgba(23, 33, 36, .22);
    background: var(--panel);
    box-shadow: 0 22px 54px rgba(23, 33, 36, .18);
  }
  .formula-card label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .answer {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 9px;
  }
  .answer input {
    width: 90px;
    border: 1px solid rgba(23,33,36,.28);
    background: #fff;
    padding: 9px 10px;
    color: var(--ink);
    font-size: 19px;
    font-weight: 900;
  }
  .answer button,
  .panel button {
    border: 0;
    background: var(--ink);
    color: #fffefa;
    padding: 10px 12px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
  }
  .feedback {
    min-height: 18px;
    margin-top: 9px;
    color: var(--teal);
    font-size: 13px;
    font-weight: 800;
    line-height: 1.35;
  }
  .panel {
    border: 1px solid var(--line);
    background: var(--panel);
    padding: 22px;
    box-shadow: 0 30px 80px rgba(23, 33, 36, .12);
  }
  .kicker {
    margin: 0 0 12px;
    color: var(--orange);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .12em;
    text-transform: uppercase;
  }
  .task {
    margin: 0 0 18px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 25px;
    line-height: 1.18;
  }
  .note {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 18px;
  }
  .control {
    display: grid;
    gap: 8px;
    padding-top: 14px;
    border-top: 1px solid var(--line);
    margin-top: 16px;
  }
  .control label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  input[type="range"] { width: 100%; accent-color: var(--orange); }
  .mode-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
  }
  .mode-row button {
    border: 1px solid rgba(23,33,36,.22);
    background: transparent;
    color: var(--ink);
  }
  .mode-row button.is-active {
    background: var(--ink);
    color: #fffefa;
  }
  .legend {
    display: grid;
    gap: 9px;
    margin-top: 18px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.4;
  }
  .legend strong { color: var(--ink); }
  @media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .scene-card { min-height: 560px; }
    .task { font-size: 22px; }
  }
</style>
</head>
<body>
<main class="shell" data-geometry-3d-demo>
  <div class="topline">
    <span>Palomatika</span>
    <span>Three.js + HTML labels</span>
  </div>
  <h1>3D-разбор геометрии</h1>

  <section class="layout" aria-label="3D пример задания 16">
    <div class="scene-card">
      <div id="threeScene" aria-label="3D модель треугольной пирамиды"></div>
      <div class="dom-layer" id="domLayer">
        <div class="label3d" data-label="A">A</div>
        <div class="label3d" data-label="B">B</div>
        <div class="label3d" data-label="C">C</div>
        <div class="label3d" data-label="S">S</div>
        <div class="angle-chip" id="angleChip">угол между SA и плоскостью ABC</div>
        <form class="formula-card" id="answerCard">
          <label for="heightAnswer">Найдите высоту SO</label>
          <div class="answer">
            <input id="heightAnswer" type="number" inputmode="numeric" placeholder="?">
            <span>см</span>
            <button type="submit">OK</button>
          </div>
          <div class="feedback" id="feedback"></div>
        </form>
      </div>
    </div>

    <aside class="panel">
      <p class="kicker">ОГЭ · номер 16 · 3D прототип</p>
      <p class="task">Основание пирамиды — прямоугольный треугольник ABC. Проекция вершины S попадает в точку O. Если AO = 8, а угол между SA и плоскостью основания равен 45°, найдите SO.</p>
      <p class="note">В обычном SVG это была бы плоская схема. Здесь можно вращать камеру, менять угол наклона и видеть, как HTML-формула остаётся настоящей DOM-карточкой, но визуально привязана к 3D-точке.</p>

      <div class="control">
        <label for="angleSlider">Угол наклона ребра SA</label>
        <input id="angleSlider" type="range" min="25" max="65" value="45">
      </div>
      <div class="control">
        <label for="spinSlider">Поворот камеры</label>
        <input id="spinSlider" type="range" min="-50" max="50" value="0">
      </div>

      <div class="mode-row">
        <button type="button" data-mode="model" class="is-active">Модель</button>
        <button type="button" data-mode="hint">Подсказка</button>
        <button type="button" data-mode="solve">Решение</button>
      </div>

      <div class="legend">
        <div><strong>HTML:</strong> подписи A, B, C, S и поле ответа не нарисованы в WebGL.</div>
        <div><strong>WebGL:</strong> плоскость, пирамида, высота и угол живут в 3D-сцене.</div>
      </div>
    </aside>
  </section>
</main>

<script type="module" src="/js/geometry-3d-demo.js"></script>
</body>
</html>
