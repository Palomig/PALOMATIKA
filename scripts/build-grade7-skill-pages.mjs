#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";

const DATA_PATH = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/skills.json";
const OUT_DIR = "/var/www/html/alg-skills/7";
const data = JSON.parse(fs.readFileSync(DATA_PATH, "utf8"));

const escapeHtml = (value) => String(value ?? "")
  .replace(/&/g, "&amp;")
  .replace(/</g, "&lt;")
  .replace(/>/g, "&gt;")
  .replace(/"/g, "&quot;")
  .replace(/'/g, "&#039;");

const hasCyrillic = (value) => /[А-Яа-яЁё]/u.test(String(value ?? ""));

const mathText = (value) => {
  const text = String(value ?? "").replace(/\s+/gu, " ").trim();
  const escaped = escapeHtml(text);

  if (!hasCyrillic(text)) {
    return `$${escaped}$`;
  }

  return escaped.replace(
    /(?<![A-Za-zА-Яа-яЁё])(-?\d+(?:,\d+)?(?:\s*(?:\\cdot|[:=+\-])\s*-?\d+(?:,\d+)?|\s*[+\-]\s*\(-?\d+(?:,\d+)?\)|\s*[:=+\-]\s*\([^)]*\)|\s*\\cdot\s*\([^)]*\)|\s*\^\d+)*)/gu,
    (match, fragment) => fragment && /\d/u.test(fragment) ? `$${fragment.trim()}$` : match,
  );
};

const levelClass = {
  simple: "level-simple",
  medium: "level-medium",
  high: "level-high",
};

const levelName = {
  simple: "Простой",
  medium: "Средний",
  high: "Высокий",
};

function shell(title, body) {
  return `<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(title)}</title>
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
    .skill-card{padding:16px;min-height:148px}
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
<body>${body}</body>
</html>`;
}

function taskSignature(expression) {
  return String(expression ?? "")
    .replace(/\\frac\{[^}]+\}\{[^}]+\}/gu, "\\frac{n}{n}")
    .replace(/-?\d+(?:,\d+)?/gu, "n")
    .replace(/\s+/gu, " ")
    .trim();
}

function taskFeatureKey(expression) {
  const text = String(expression ?? "");
  const features = [];
  if (text.includes("\\frac")) features.push("fractions");
  if (/\([xyz]\s*[-+]\s*\d+\)/u.test(text)) features.push("shifted-variable");
  if (/=\s*-?\d+[xyz]\b/u.test(text)) features.push("variable-right");
  if (text.includes("z")) features.push("three-vars");
  if ((text.match(/\\\\/g)?.length ?? 0) >= 2) features.push("three-equations");
  if ((text.match(/\(/g)?.length ?? 0) >= 2) features.push("nested-parentheses");
  return features.length ? features.join("|") : "plain";
}

function representativeTasks(tasks, limit = 12) {
  const seen = new Set();
  const picked = [];

  for (const task of tasks) {
    const signature = `${taskFeatureKey(task.expression)}::${taskSignature(task.expression)}`;
    if (seen.has(signature)) continue;
    seen.add(signature);
    picked.push({ ...task, id: picked.length + 1 });
    if (picked.length >= limit) break;
  }

  return picked.length ? picked : tasks.slice(0, Math.min(tasks.length, limit));
}

function taskCard(task) {
  return `<div class="task">
    <button class="flag" title="Пометить">⚑</button>
    <div class="expr-line"><span class="num">${task.id})</span><span class="expr">${mathText(task.expression)}</span></div>
    <div class="answer"><span>Ответ:</span> <b>${String(task.answer).includes("\\") ? mathText(task.answer) : escapeHtml(task.answer)}</b> <span class="muted">[AI]</span></div>
    <div class="status"><span></span>PROD</div>
  </div>`;
}

function homeworkCard(set) {
  return `<article class="homework-card">
    <h3>${escapeHtml(set.title)}</h3>
    <p>${set.tasks_count} заданий · ${set.target_minutes} мин · простой ${set.mix.simple}, средний ${set.mix.medium}, высокий ${set.mix.high}</p>
    <details>
      <summary>Показать задания</summary>
      <ol>${set.tasks.map(task => `<li>${mathText(task.expression)}</li>`).join("")}</ol>
    </details>
  </article>`;
}

function skillPage(skill) {
  const body = `<div class="wrap skill-page" data-task-view="types">
    <a href="/alg-skills/7/">← Все навыки 7 класса</a>
    <header class="top">
      <div class="eyebrow">PALOMATIKA · Алгебра · 7 класс · ${escapeHtml(skill.group_title)}</div>
      <h1>${skill.id}. ${escapeHtml(skill.title)}</h1>
      <div class="muted">Одна страница тренирует один конкретный навык. Уровни отличаются только сложностью чисел и количеством шагов, а не типом задания.</div>
      <div class="stats">
        <div class="stat"><b>${skill.tasks_count}</b> задач</div>
        <div class="stat"><b>3</b> уровня</div>
        <div class="stat"><b>${escapeHtml(skill.task_type)}</b> тип</div>
      </div>
      <div class="view-switch" aria-label="Режим просмотра заданий">
        <button class="is-active" type="button" data-view-button="types">Типы примеров</button>
        <button type="button" data-view-button="all">Все задания</button>
      </div>
    </header>
    <div class="types-view">
      <section class="panel">
        <h2>Типы примеров</h2>
        <div class="muted">Показаны разные шаблоны заданий без повторов числовых вариантов. Этот режим нужен для быстрой оценки разнообразия.</div>
      </section>
      ${skill.levels.map(level => `<section class="panel ${levelClass[level.id]}">
        <div class="level-head">
          <div>
            <h2>${escapeHtml(level.title)}</h2>
            <div class="muted">${escapeHtml(level.description)}</div>
          </div>
          <div class="level-badge">${levelName[level.id]}</div>
        </div>
        <div class="taskgrid">${representativeTasks(level.tasks).map(taskCard).join("")}</div>
      </section>`).join("")}
    </div>
    <div class="all-view">
      <section class="panel">
        <h2>Готовые домашки</h2>
        <div class="homework-list">${skill.homework_sets.map(homeworkCard).join("")}</div>
      </section>
      ${skill.levels.map(level => `<section class="panel ${levelClass[level.id]}">
        <div class="level-head">
          <div>
            <h2>${escapeHtml(level.title)}</h2>
            <div class="muted">${escapeHtml(level.description)}</div>
          </div>
          <div class="level-badge">${levelName[level.id]}</div>
        </div>
        <div class="taskgrid">${level.tasks.map(taskCard).join("")}</div>
      </section>`).join("")}
    </div>
  </div>`;

  return shell(`${skill.title} · Алгебра 7 класс`, body);
}

function indexPage() {
  const groups = data.groups
    .map(group => ({
      ...group,
      skills: data.skills.filter(skill => skill.group === group.id),
    }))
    .filter(group => group.skills.length);

  const body = `<div class="wrap">
    <a href="/alg-topics/7/0/">← Арифметическая база</a>
    <header class="top">
      <div class="eyebrow">PALOMATIKA · Алгебра · 7 класс</div>
      <h1>Банк навыков</h1>
      <div class="muted">Не главы учебника, а отдельные действия ученика. Репетитор выбирает ровно тот навык, который нужно добить на уроке или в домашке.</div>
      <div class="stats">
        <div class="stat"><b>${data.skills.length}</b> навыков</div>
        <div class="stat"><b>${data.skills.reduce((sum, skill) => sum + skill.tasks_count, 0)}</b> задач</div>
        <div class="stat"><b>3</b> уровня на каждый навык</div>
      </div>
    </header>
    ${groups.map(group => `<section>
      <h2 class="group-title">${escapeHtml(group.title)}</h2>
      <div class="grid">${group.skills.map(skill => `<a class="skill-card" href="/alg-skills/7/${skill.slug}/">
        <div class="id">${skill.id}</div>
        <div class="title">${escapeHtml(skill.title)}</div>
        <div class="group">${escapeHtml(skill.group_title)}</div>
        <div class="chips">
          <span class="chip">${skill.tasks_count} задач</span>
          <span class="chip">3 домашки</span>
          <span class="chip">3 уровня</span>
        </div>
      </a>`).join("")}</div>
    </section>`).join("")}
  </div>`;

  return shell("Банк навыков · Алгебра 7 класс", body);
}

fs.rmSync(OUT_DIR, { recursive: true, force: true });
fs.mkdirSync(OUT_DIR, { recursive: true });
fs.writeFileSync(path.join(OUT_DIR, "index.html"), indexPage());
fs.writeFileSync(path.join(OUT_DIR, "skills.json"), JSON.stringify(data, null, 2));

for (const skill of data.skills) {
  const dir = path.join(OUT_DIR, skill.slug);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, "index.html"), skillPage(skill));
}

console.log(`built ${data.skills.length} skill pages -> ${OUT_DIR}`);
