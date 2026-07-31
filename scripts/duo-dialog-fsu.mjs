#!/usr/bin/env node
// Duo-dialog (MiniMax ↔ DeepSeek) classifier for ФСУ tasks across topics 07/08/09.
//
// For every task it produces one of three labels:
//   "base"     — степени 1–2 (стандартный уровень 7 класса)
//   "advanced" — присутствует степень 3 (кубы) → блок "Повышенный уровень"
//   "extra"    — присутствует степень ≥4 → блок "Дополнительный уровень" (используется редко)
//
// Pipeline:
//   1. Heuristic seed (regex on expression / instruction)
//   2. DeepSeek reviews seed in batches → may flip labels
//   3. MiniMax reviews DeepSeek output → may flip labels
//   4. If labels still disagree → marked "unresolved" for manual review
//
// Output: scripts/fsu-classification.json  (does NOT modify topic files)
//
// Usage:
//   node --env-file=.env scripts/duo-dialog-fsu.mjs           # full run
//   node --env-file=.env scripts/duo-dialog-fsu.mjs --dry     # heuristic only
//   node --env-file=.env scripts/duo-dialog-fsu.mjs --topic 09  # one topic

import fs from "node:fs";
import path from "node:path";

const DRY = process.argv.includes("--dry");
const TOPIC_FILTER = (() => {
  const i = process.argv.indexOf("--topic");
  return i === -1 ? null : process.argv[i + 1].padStart(2, "0");
})();
const BATCH = 20;
const CONCURRENCY = 4;

const MM_KEY = process.env.MINIMAX_API_KEY;
const DS_KEY = process.env.DEEPSEEK_API_KEY;
if (!DRY && (!MM_KEY || !DS_KEY)) {
  console.error("MINIMAX_API_KEY or DEEPSEEK_API_KEY missing"); process.exit(1);
}

const TOPIC_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const OUT = "/home/dev/palomatika/scripts/fsu-classification.json";
const TOPICS = ["07", "08", "09"].filter(t => !TOPIC_FILTER || t === TOPIC_FILTER);

// ---------- 1. Collect tasks ----------
function collect() {
  const items = [];
  for (const tid of TOPICS) {
    const file = path.join(TOPIC_DIR, `topic_${tid}.json`);
    const topic = JSON.parse(fs.readFileSync(file, "utf8"));
    topic.blocks.forEach((block, bi) => {
      block.zadaniya.forEach((zad, zi) => {
        zad.tasks.forEach((task, ti) => {
          const text = [zad.instruction || "", task.expression || "", task.answer || ""].join(" \n ");
          items.push({
            id: `t${tid}_b${bi}_z${zi}_t${ti}`,
            topic: tid,
            block_idx: bi,
            zad_idx: zi,
            task_idx: ti,
            instruction: zad.instruction || "",
            expression: task.expression || "",
            answer: task.answer || "",
            text
          });
        });
      });
    });
  }
  return items;
}

// ---------- 2. Heuristic seed ----------
const RE_HIGH_POWER_NUM = /\^(?:0*[4-9]|0*\d{2,})\b/;          // ^4, ^5, ^10 ...
const RE_HIGH_POWER_RU  = /\b(?:четвёрт|пят|шест|седьм|восьм|девят|десят)(?:ой|ая|ую|ого|ом)?\s+степен/i;
const RE_CUBE_NUM       = /\^0*3\b/;
const RE_CUBE_RU        = /\bкуб(?:а|ов|у|е|ом|ы|ах)?\b|\bтрет(?:ьей|ий|ьего|ьем)?\s+степен|формул[аеуы].{0,30}куб|сумм[ыа].{0,5}куб|разност[иь].{0,5}куб/i;

function seed(text) {
  if (RE_HIGH_POWER_NUM.test(text) || RE_HIGH_POWER_RU.test(text)) return "extra";
  if (RE_CUBE_NUM.test(text) || RE_CUBE_RU.test(text)) return "advanced";
  return "base";
}

// ---------- 3. Model callers ----------
async function callDeepSeek(messages) {
  const t0 = Date.now();
  const res = await fetch("https://api.deepseek.com/v1/chat/completions", {
    method: "POST",
    headers: { "Authorization": `Bearer ${DS_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ model: "deepseek-v4-pro", messages, temperature: 0.1, max_tokens: 4096 })
  });
  const txt = await res.text();
  if (!res.ok) throw new Error(`DeepSeek ${res.status}: ${txt.slice(0, 300)}`);
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
  if (!res.ok) throw new Error(`MiniMax ${res.status}: ${txt.slice(0, 300)}`);
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

// ---------- 4. Build review prompts ----------
const SYSTEM = `Ты — методист по школьной математике (Россия, 7 класс), специализация: формулы сокращённого умножения (ФСУ).

Тебе дают пакет задач. Для каждой задачи нужно вернуть один из трёх лейблов:
  "base"     — задача использует только степени 1 и 2 (квадраты), стандартная программа 7 класса.
  "advanced" — задача требует работы с кубами (степень 3): формула куба суммы/разности, сумма/разность кубов, разложение на множители кубов и т.п.
  "extra"    — задача требует работы со степенями 4 и выше (формула 4-й степени двучлена, (a+b)^5 и т.п.).

ВАЖНО — ориентируйся на МАКСИМАЛЬНУЮ степень, которая встречается в задаче ИЛИ возникает естественным образом при её решении (раскрытие скобок, перемножение, ответ).
- (a^2)^2 = a^4 формально появляется при раскрытии (a-b)(a+b) с a=x^2, но сам приём — base.
- (x^3 + 6x)^2 после раскрытия даёт x^6 — это extra, даже если в условии видна только ^3.
- Куб двучлена (a+b)^3, сумма/разность кубов a^3±b^3 — это advanced.
- Формула 4-й/5-й степени двучлена, (a+b)^4 и выше — это extra.
- Квадрат выражения, содержащего ^3 или выше: смотри, что получится в результате. Если результат имеет степень ≥6 — extra, если ≤4 в стандартной программе — advanced.
- Если сомневаешься между base и advanced — выбирай advanced.
- Если сомневаешься между advanced и extra — выбирай extra.

Возвращай СТРОГО валидный JSON-массив объектов вида:
[{"id": "<тот же id>", "label": "base|advanced|extra", "note": "<короткое обоснование>"}, ...]
Никаких пояснений вне JSON.`;

function buildUserPrompt(items, prevLabels) {
  const list = items.map(it => {
    const prev = prevLabels ? `  prev_label: ${prevLabels[it.id]}\n` : "";
    const ans = it.answer ? `\n  answer: ${JSON.stringify(it.answer).slice(0, 200)}` : "";
    return `id: ${it.id}\n${prev}  instruction: ${JSON.stringify(it.instruction).slice(0, 300)}\n  expression: ${JSON.stringify(it.expression).slice(0, 400)}${ans}`;
  }).join("\n---\n");
  const head = prevLabels
    ? "Проверь предыдущую разметку. Подтверди или исправь label для каждой задачи. Если согласен — просто повтори тот же label."
    : "Расставь label для каждой задачи.";
  return `${head}\n\nЗадачи:\n${list}`;
}

// ---------- 5. Pipeline ----------
function chunk(arr, n) { const out = []; for (let i = 0; i < arr.length; i += n) out.push(arr.slice(i, i + n)); return out; }

async function processBatch(callFn, batch, prevLabels, who, idx, total) {
  const prompt = buildUserPrompt(batch, prevLabels);
  let r;
  try {
    r = await callFn([{ role: "system", content: SYSTEM }, { role: "user", content: prompt }]);
  } catch (e) {
    console.log(`  [${who}] batch ${idx + 1}/${total} ERROR: ${e.message}`);
    return Object.fromEntries(batch.map(it => [it.id, prevLabels?.[it.id] || "unresolved"]));
  }
  let parsed;
  try { parsed = JSON.parse(extractJson(r.content)); }
  catch (e) {
    console.log(`  [${who}] batch ${idx + 1}/${total} parse error: ${e.message}`);
    return Object.fromEntries(batch.map(it => [it.id, prevLabels?.[it.id] || "unresolved"]));
  }
  if (!Array.isArray(parsed)) {
    return Object.fromEntries(batch.map(it => [it.id, prevLabels?.[it.id] || "unresolved"]));
  }
  const map = new Map(parsed.map(p => [p.id, p]));
  const result = {};
  let flips = 0;
  for (const it of batch) {
    const v = map.get(it.id);
    const lbl = v && ["base", "advanced", "extra"].includes(v.label) ? v.label : (prevLabels?.[it.id] || "unresolved");
    if (prevLabels && prevLabels[it.id] !== lbl) flips++;
    result[it.id] = lbl;
  }
  console.log(`  [${who}] batch ${idx + 1}/${total}  ${batch.length} items, ${flips} flips, ${r.ms}ms, tokens ${r.usage.prompt_tokens || 0}→${r.usage.completion_tokens || 0}`);
  return result;
}

async function reviewBatches(callFn, items, prevLabels, who) {
  const batches = chunk(items, BATCH);
  const final = {};
  let cursor = 0;
  async function worker() {
    while (true) {
      const i = cursor++;
      if (i >= batches.length) return;
      const partial = await processBatch(callFn, batches[i], prevLabels, who, i, batches.length);
      Object.assign(final, partial);
    }
  }
  await Promise.all(Array.from({ length: Math.min(CONCURRENCY, batches.length) }, worker));
  return final;
}

// ---------- main ----------
const items = collect();
console.log(`Collected ${items.length} tasks across ${TOPICS.length} topics`);

const seedLabels = Object.fromEntries(items.map(it => [it.id, seed(it.text)]));
const seedDist = items.reduce((a, it) => { a[seedLabels[it.id]] = (a[seedLabels[it.id]] || 0) + 1; return a; }, {});
console.log(`Heuristic seed: base=${seedDist.base || 0}, advanced=${seedDist.advanced || 0}, extra=${seedDist.extra || 0}`);

if (DRY) {
  fs.writeFileSync(OUT, JSON.stringify({
    generated_at: new Date().toISOString(),
    mode: "dry",
    items: items.map(it => ({ ...it, text: undefined, seed: seedLabels[it.id], deepseek: null, minimax: null, final: seedLabels[it.id] }))
  }, null, 2));
  console.log(`Dry run written to ${OUT}`);
  process.exit(0);
}

console.log(`\n=== DeepSeek pass ===`);
const dsLabels = await reviewBatches(callDeepSeek, items, seedLabels, "DeepSeek");

console.log(`\n=== MiniMax pass ===`);
const mmLabels = await reviewBatches(callMiniMax, items, dsLabels, "MiniMax");

// final = MiniMax's call; mark unresolved if MM disagrees with DS AND seed disagrees with MM
let unresolved = 0;
const final = {};
for (const it of items) {
  const s = seedLabels[it.id], d = dsLabels[it.id], m = mmLabels[it.id];
  // unresolved if all three differ
  const set = new Set([s, d, m]);
  if (set.size === 3) { final[it.id] = "unresolved"; unresolved++; }
  else final[it.id] = m;
}

const finalDist = items.reduce((a, it) => { a[final[it.id]] = (a[final[it.id]] || 0) + 1; return a; }, {});
console.log(`\n=== Final ===`);
console.log(`base=${finalDist.base || 0}, advanced=${finalDist.advanced || 0}, extra=${finalDist.extra || 0}, unresolved=${unresolved}`);

const out = {
  generated_at: new Date().toISOString(),
  topics: TOPICS,
  total: items.length,
  distribution: finalDist,
  items: items.map(it => ({
    id: it.id, topic: it.topic, block_idx: it.block_idx, zad_idx: it.zad_idx, task_idx: it.task_idx,
    instruction: it.instruction.slice(0, 200), expression: it.expression.slice(0, 300),
    seed: seedLabels[it.id], deepseek: dsLabels[it.id], minimax: mmLabels[it.id], final: final[it.id]
  }))
};
fs.writeFileSync(OUT, JSON.stringify(out, null, 2));
console.log(`Written → ${OUT}`);
