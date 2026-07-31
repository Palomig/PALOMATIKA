#!/usr/bin/env node
// Duo-dialog difficulty classifier for non-FSU grade-7 topics (01–06, 10, 11).
// Ориентируется на прогрессию учебника: первые задачи zadanie проще, дальше сложнее;
// дроби, многошаговые преобразования, доказательства, параметры → повышенный/доп.

import fs from "node:fs";
import path from "node:path";

const MM_KEY = process.env.MINIMAX_API_KEY;
const DS_KEY = process.env.DEEPSEEK_API_KEY;
if (!MM_KEY || !DS_KEY) { console.error("MINIMAX_API_KEY or DEEPSEEK_API_KEY missing"); process.exit(1); }

const BATCH = 20;
const CONCURRENCY = 4;
const TOPIC_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const OUT = "/home/dev/palomatika/scripts/difficulty-classification.json";
const TOPICS = ["01", "02", "03", "04", "05", "06", "10", "11"];

function collect() {
  const items = [];
  for (const tid of TOPICS) {
    const file = path.join(TOPIC_DIR, `topic_${tid}.json`);
    const topic = JSON.parse(fs.readFileSync(file, "utf8"));
    topic.blocks.forEach((block, bi) => {
      block.zadaniya.forEach((zad, zi) => {
        zad.tasks.forEach((task, ti) => {
          items.push({
            id: `t${tid}_b${bi}_z${zi}_t${ti}`,
            topic: tid,
            topic_title: topic.meta?.title || "",
            block_idx: bi,
            block_title: block.title || "",
            zad_idx: zi,
            zad_total: zad.tasks.length,
            task_idx: ti,
            instruction: zad.instruction || "",
            expression: task.expression || "",
            answer: task.answer || ""
          });
        });
      });
    });
  }
  return items;
}

function seed(it) {
  const text = `${it.instruction} ${it.expression} ${it.answer}`;
  const pos = it.zad_total > 1 ? it.task_idx / (it.zad_total - 1) : 0;
  const hasFraction = /\b\d+\s*\/\s*\d+|\b\d+,\d+|\b\d+\s+\d+\s*\/\s*\d+/.test(text);
  const hasParam = /\bпараметр|при каком значении|при всех значениях/i.test(text);
  const isProof = /\bдокажите|доказать|тождество|для всех\b/i.test(it.instruction);
  const longExpr = it.expression.length > 120;

  // Heuristic seed: pos in zadanie + structural complexity
  let score = 0;
  score += pos * 2;
  if (hasFraction) score += 1;
  if (hasParam) score += 2;
  if (isProof) score += 1.5;
  if (longExpr) score += 0.5;

  if (score >= 3) return "extra";
  if (score >= 1.5) return "advanced";
  return "base";
}

const SYSTEM = `Ты — методист, оценивающий сложность задач по алгебре 7 класса (Россия, ФГОС).
Тебе дают пакет задач из учебника. Для каждой расставь лейбл сложности:

  "base"     — типовая, простая, для отработки базового приёма. В zadanie такие идут первыми.
  "advanced" — сложнее средней: больше шагов, есть дроби (обыкновенные или десятичные), нестандартное преобразование, требует комбинации нескольких приёмов. Обычно идёт во второй половине zadanie.
  "extra"    — повышенной сложности (олимпиадная, на доказательство, с параметром, длинная цепочка, концептуально трудная).

Эвристики:
- Прогрессия в zadanie: первые задачи (по индексу) обычно base, последние — advanced/extra.
- Если есть дроби (2/3, 0.5, 1 1/4) — на ступень выше.
- "Докажите", "тождество", "при каком значении", "при всех" → обычно advanced или extra.
- Текстовые задачи (про объёмы, площади, движение) → не ниже advanced.
- Очень длинное выражение или несколько вложенных скобок → не ниже advanced.
- Если zadanie целиком про разминку (короткие выражения, инструкция типа "вычислите", "упростите") — большинство base.

Возвращай СТРОГО валидный JSON-массив:
[{"id": "<id>", "label": "base|advanced|extra"}]
Никакого текста вне JSON.`;

function buildPrompt(items, prevLabels) {
  const list = items.map(it => {
    const prev = prevLabels ? `  prev_label: ${prevLabels[it.id]}\n` : "";
    return `id: ${it.id}
  topic: "${it.topic_title}"
  block: "${it.block_title}"
  zadanie_position: ${it.task_idx + 1}/${it.zad_total}
${prev}  instruction: ${JSON.stringify(it.instruction).slice(0, 250)}
  expression: ${JSON.stringify(it.expression).slice(0, 350)}${it.answer ? `\n  answer: ${JSON.stringify(it.answer).slice(0, 150)}` : ""}`;
  }).join("\n---\n");
  return (prevLabels ? "Проверь предыдущие лейблы — подтверди или исправь." : "Расставь лейблы.") + "\n\n" + list;
}

async function callDeepSeek(messages) {
  const t0 = Date.now();
  const res = await fetch("https://api.deepseek.com/v1/chat/completions", {
    method: "POST",
    headers: { "Authorization": `Bearer ${DS_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ model: "deepseek-v4-pro", messages, temperature: 0.1, max_tokens: 4096 })
  });
  const txt = await res.text();
  if (!res.ok) throw new Error(`DeepSeek ${res.status}`);
  const json = JSON.parse(txt);
  return { content: json.choices?.[0]?.message?.content || "", ms: Date.now() - t0, usage: json.usage || {} };
}
async function callMiniMax(messages) {
  const t0 = Date.now();
  const res = await fetch("https://api.minimax.io/v1/text/chatcompletion_v2", {
    method: "POST",
    headers: { "Authorization": `Bearer ${MM_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ model: "MiniMax-M2.7", messages, temperature: 0.1, max_tokens: 4096 })
  });
  const txt = await res.text();
  if (!res.ok) throw new Error(`MiniMax ${res.status}`);
  const json = JSON.parse(txt);
  return { content: json.choices?.[0]?.message?.content || "", ms: Date.now() - t0, usage: json.usage || {} };
}

function stripThink(s) { return String(s).replace(/<think>[\s\S]*?<\/think>/g, "").trim(); }
function extractJson(s) {
  const c = stripThink(s);
  const fence = c.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const a = c.search(/[[]/), b = c.lastIndexOf("]");
  return (a !== -1 && b > a) ? c.slice(a, b + 1) : c;
}

function chunk(arr, n) { const out = []; for (let i = 0; i < arr.length; i += n) out.push(arr.slice(i, i + n)); return out; }

async function processBatch(callFn, batch, prev, who, idx, total) {
  const messages = [{ role: "system", content: SYSTEM }, { role: "user", content: buildPrompt(batch, prev) }];
  let r;
  try { r = await callFn(messages); }
  catch (e) {
    console.log(`  [${who}] ${idx + 1}/${total} ERROR: ${e.message}`);
    return Object.fromEntries(batch.map(it => [it.id, prev?.[it.id] || "base"]));
  }
  let parsed;
  try { parsed = JSON.parse(extractJson(r.content)); }
  catch (e) {
    console.log(`  [${who}] ${idx + 1}/${total} parse error`);
    return Object.fromEntries(batch.map(it => [it.id, prev?.[it.id] || "base"]));
  }
  if (!Array.isArray(parsed)) return Object.fromEntries(batch.map(it => [it.id, prev?.[it.id] || "base"]));
  const map = new Map(parsed.map(p => [p.id, p]));
  const out = {};
  let flips = 0;
  for (const it of batch) {
    const v = map.get(it.id);
    const lbl = v && ["base", "advanced", "extra"].includes(v.label) ? v.label : (prev?.[it.id] || "base");
    if (prev && prev[it.id] !== lbl) flips++;
    out[it.id] = lbl;
  }
  console.log(`  [${who}] ${idx + 1}/${total}  ${batch.length} items, ${flips} flips, ${r.ms}ms`);
  return out;
}

async function reviewBatches(callFn, items, prev, who) {
  const batches = chunk(items, BATCH);
  const final = {};
  let cursor = 0;
  async function worker() {
    while (true) {
      const i = cursor++;
      if (i >= batches.length) return;
      Object.assign(final, await processBatch(callFn, batches[i], prev, who, i, batches.length));
    }
  }
  await Promise.all(Array.from({ length: Math.min(CONCURRENCY, batches.length) }, worker));
  return final;
}

const items = collect();
console.log(`Collected ${items.length} tasks across ${TOPICS.length} topics`);
const seedLabels = Object.fromEntries(items.map(it => [it.id, seed(it)]));
const sd = items.reduce((a, it) => ((a[seedLabels[it.id]] = (a[seedLabels[it.id]] || 0) + 1), a), {});
console.log(`Seed: base=${sd.base || 0}, advanced=${sd.advanced || 0}, extra=${sd.extra || 0}`);

console.log("\n=== DeepSeek pass ===");
const ds = await reviewBatches(callDeepSeek, items, seedLabels, "DS");
console.log("\n=== MiniMax pass ===");
const mm = await reviewBatches(callMiniMax, items, ds, "MM");

const final = {};
let unresolved = 0;
for (const it of items) {
  const set = new Set([seedLabels[it.id], ds[it.id], mm[it.id]]);
  if (set.size === 3) { final[it.id] = "advanced"; unresolved++; }  // на нейтральный для difficulty
  else final[it.id] = mm[it.id];
}
const fd = items.reduce((a, it) => ((a[final[it.id]] = (a[final[it.id]] || 0) + 1), a), {});
console.log(`\nFinal: base=${fd.base || 0}, advanced=${fd.advanced || 0}, extra=${fd.extra || 0}, 3-way=${unresolved}`);

fs.writeFileSync(OUT, JSON.stringify({
  generated_at: new Date().toISOString(),
  topics: TOPICS,
  total: items.length,
  distribution: fd,
  items: items.map(it => ({
    id: it.id, topic: it.topic, block_idx: it.block_idx, zad_idx: it.zad_idx, task_idx: it.task_idx,
    seed: seedLabels[it.id], deepseek: ds[it.id], minimax: mm[it.id], final: final[it.id]
  }))
}, null, 2));
console.log(`Written → ${OUT}`);
