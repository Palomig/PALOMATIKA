#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";

const root = "/home/dev/palomatika";
const topicPath = path.join(root, "storage/app/tasks/alg/grade_7/topic_00.json");
const outDir = "/var/www/html/alg-topics/7/0";
const topic = JSON.parse(fs.readFileSync(topicPath, "utf8"));

const escapeHtml = (value) => String(value ?? "")
  .replace(/&/g, "&amp;")
  .replace(/</g, "&lt;")
  .replace(/>/g, "&gt;")
  .replace(/"/g, "&quot;")
  .replace(/'/g, "&#039;");

const hasCyrillic = (value) => /[А-Яа-яЁё]/u.test(value);

const mathText = (value) => {
  const text = String(value ?? "").replace(/\s+/gu, " ").trim();
  const escaped = escapeHtml(text);

  if (!hasCyrillic(text)) {
    return `$${escaped}$`;
  }

  return escaped.replace(
    /(?<![A-Za-zА-Яа-яЁё])(-?\d+(?:,\d+)?(?:\s*(?:\\cdot|[:=+\-])\s*-?\d+(?:,\d+)?|\s*[+\-]\s*\(-?\d+(?:,\d+)?\)|\s*[:=+\-]\s*\([^)]*\)|\s*\\cdot\s*\([^)]*\))*)/gu,
    (match, fragment) => fragment && /\d/u.test(fragment) ? `$${fragment.trim()}$` : match,
  );
};

const taskCard = (task, index) => `
<div class="task">
  <button class="flag" title="Пометить">⚑</button>
  <div class="expr-line"><span class="num">${index})</span><span class="expr">${mathText(task.expression ?? task.prompt ?? "")}</span></div>
  <div class="answer"><span>Ответ:</span> <b>${String(task.answer ?? "").includes("\\") ? mathText(task.answer) : escapeHtml(task.answer ?? "")}</b> <span class="source">[AI]</span></div>
  <div class="status"><span></span>PROD</div>
</div>`;

const seriesHtml = topic.blocks.map((block) => `
<section class="block">
  <h2>Блок ${block.number}. ${escapeHtml(block.title)}</h2>
  ${block.zadaniya.map((zadanie) => `
    <div class="series">
      <h3>Задание ${zadanie.number}. ${escapeHtml(zadanie.instruction)} <span>${(zadanie.tasks ?? []).length} шт.</span></h3>
      <div class="taskgrid">${(zadanie.tasks ?? []).map((task, i) => taskCard(task, i + 1)).join("")}</div>
    </div>
  `).join("")}
</section>`).join("");

const totalSeries = topic.blocks.reduce((sum, block) => sum + (block.zadaniya ?? []).length, 0);
const totalTasks = topic.blocks.reduce((sum, block) => sum + (block.zadaniya ?? []).reduce((inner, zadanie) => inner + (zadanie.tasks ?? []).length, 0), 0);

const html = `<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Алгебра 7 класс · Тема 0</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js" onload="renderMathInElement(document.body,{delimiters:[{left:'$',right:'$',display:false},{left:'$$',right:'$$',display:true}],throwOnError:false})"></script>
  <style>
    :root{color-scheme:dark}
    *{box-sizing:border-box}
    body{margin:0;background:#101122;color:#edf2f7;font:15px/1.55 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
    .wrap{max-width:1180px;margin:0 auto;padding:34px 20px 80px}
    a{color:#60a5fa;text-decoration:none}
    .top{border-bottom:1px solid rgba(148,163,184,.22);padding-bottom:22px;margin-bottom:24px}
    .h1{font-size:36px;line-height:1.1;margin:8px 0;letter-spacing:0}
    .muted{color:#94a3b8}
    .stats{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
    .stat{border:1px solid rgba(148,163,184,.22);background:#172033;padding:9px 12px;border-radius:8px}
    .stat b{color:#60a5fa}
    .panel{background:#172033;border:1px solid rgba(148,163,184,.22);border-radius:8px;padding:20px;margin:24px 0}
    .taskgrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
    .task{position:relative;background:#172033;border:1px solid #304158;border-radius:8px;padding:14px 12px;min-height:118px}
    .flag{position:absolute;right:10px;top:10px;width:28px;height:28px;border:0;border-radius:6px;background:#263449;color:#92a0b4;cursor:pointer}
    .expr-line{display:flex;gap:10px;align-items:flex-start;padding-right:32px}
    .num{color:#60a5fa;font-weight:800;font-size:17px}
    .expr{font-family:"KaTeX_Main","Times New Roman",serif;font-size:18px;font-weight:650;color:#f8fafc;min-width:0;overflow-x:auto;white-space:nowrap}
    .expr .katex{font-size:1.08em}
    .answer{color:#7890b2;font-size:13px;margin-top:16px}
    .answer b{color:#34d399;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
    .source{color:#93a4bd;margin-left:4px}
    .status{margin-top:8px;color:#34d399;font-weight:800;font-size:11px}
    .status span{display:inline-block;width:8px;height:8px;background:#34d399;border-radius:999px;margin-right:4px}
    h2{font-size:22px;margin:0 0 12px;letter-spacing:0}
    h3{font-size:17px;margin:16px 0 12px;letter-spacing:0}
    h3 span{color:#94a3b8;font-weight:500}
    .block{margin:34px 0}
    .series{margin:20px 0}
    @media(max-width:960px){.taskgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.taskgrid{grid-template-columns:1fr}.h1{font-size:28px}.wrap{padding-inline:14px}}
  </style>
</head>
<body>
  <div class="wrap">
    <a href="/alg-topics/7/">← Все темы 7 класса</a>
    <header class="top">
      <div class="muted">PALOMATIKA · Алгебра · 7 класс</div>
      <h1 class="h1">Тема 0. ${escapeHtml(topic.meta.title)}</h1>
      <div class="muted">${escapeHtml(topic.meta.description)}</div>
      <div class="stats">
        <div class="stat"><b>${(topic.micro_skills ?? []).length}</b> микронавыков</div>
        <div class="stat"><b>${totalSeries}</b> серий</div>
        <div class="stat"><b>${totalTasks}</b> задач</div>
        <div class="stat"><b>${(topic.homework_sets?.[0]?.tasks ?? []).length}</b> заданий в первой домашке</div>
      </div>
    </header>
    <section class="panel">
      <h2>Главная идея</h2>
      ${escapeHtml(topic.curriculum.main_idea)}
    </section>
    ${seriesHtml}
  </div>
</body>
</html>`;

fs.mkdirSync(outDir, { recursive: true });
fs.writeFileSync(path.join(outDir, "index.html"), html);
console.log(`built ${path.join(outDir, "index.html")}`);
