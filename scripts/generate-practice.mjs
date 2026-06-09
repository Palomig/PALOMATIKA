#!/usr/bin/env node
// Generate practice tasks via DeepSeek using few-shot examples from Makarychev textbook.
// Usage: node --env-file=.env scripts/generate-practice.mjs <topicId> [--per-zad N] [--max-zad K]
//
// For each zadanie group with enough source examples, sends 3-5 of them to DeepSeek
// and asks for N analogous problems. Validates JSON structure, dedupes,
// adds as a NEW BLOCK 2 "Сгенерированные DeepSeek" in the topic file.

import fs from "node:fs";
import path from "node:path";

const KEY = process.env.DEEPSEEK_API_KEY;
if (!KEY) { console.error("DEEPSEEK_API_KEY missing"); process.exit(1); }

const ENDPOINT = "https://api.deepseek.com/v1/chat/completions";
const MODEL = "deepseek-v4-pro";
const PRICE_IN  = 0.435 / 1_000_000;
const PRICE_OUT = 0.870 / 1_000_000;

// --- Args ---
const args = process.argv.slice(2);
if (!args[0]) { console.error("Usage: generate-practice.mjs <topicId> [--per-zad N] [--max-zad K] [--min-source M]"); process.exit(1); }
const topicId = args[0].padStart(2, "0");
const getArg = (name, def) => {
  const i = args.indexOf("--" + name);
  return i === -1 ? def : Number(args[i + 1]);
};
const PER_ZAD = getArg("per-zad", 8);
const MAX_ZAD = getArg("max-zad", 10);
const MIN_SOURCE = getArg("min-source", 3);   // need at least M source examples per zadanie
const MAX_FEWSHOT = 5;

const TOPIC_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const SRC_FILE = path.join(TOPIC_DIR, `topic_${topicId}.json`);
if (!fs.existsSync(SRC_FILE)) { console.error("topic file not found:", SRC_FILE); process.exit(1); }

const topic = JSON.parse(fs.readFileSync(SRC_FILE, "utf8"));
const sourceBlock = topic.blocks[0];

console.log(`=== Generating practice for topic_${topicId}: "${topic.meta.title}" ===`);
console.log(`source zadaniya: ${sourceBlock.zadaniya.length}, target per zad: ${PER_ZAD}, max zad: ${MAX_ZAD}\n`);

// --- Build prompt ---
const SYSTEM = `Ты — опытный репетитор математики 7 класса.
Твоя задача: генерировать НОВЫЕ практические задачи в стиле учебника Макарычева.

ОЧЕНЬ ВАЖНО:
- Возвращай СТРОГО валидный JSON-массив. Никаких префиксов, комментариев, объяснений.
- ASCII-математика: ^, *, /, =, без LaTeX, без unicode-степеней (²→^2)
- Дроби: "5/6", смешанные: "1 2/5", степени: "x^2"
- Все задачи решаемы, корни/значения целые или с одной десятичной
- Не повторяй задачи из примеров и не дублируй внутри ответа
- Сложность не выше школьного 7 класса`;

function buildUserPrompt(instruction, samples, count) {
  const fewshot = samples.slice(0, MAX_FEWSHOT)
    .map((s, i) => `${i + 1}. ${s}`).join("\n");
  return `Тема: ${topic.meta.title}
Инструкция: "${instruction}"

Примеры из учебника:
${fewshot}

Сгенерируй ${count} НОВЫХ задач того же типа и сложности.
Формат ответа — JSON-массив:
[
  { "expression": "...", "answer": "..." },
  ...
]

В "expression" — само выражение/уравнение/задача (как в примерах выше).
В "answer" — ответ в виде строки. Если задача требует разложения/упрощения — пиши результат. Если уравнение — корень. Если несколько корней — через запятую.`;
}

// --- DeepSeek call with retries ---
async function callDeepSeek(userPrompt, attempt = 0) {
  const t0 = Date.now();
  let res, txt, json;
  try {
    res = await fetch(ENDPOINT, {
      method: "POST",
      headers: { "Authorization": `Bearer ${KEY}`, "Content-Type": "application/json" },
      body: JSON.stringify({
        model: MODEL,
        messages: [
          { role: "system", content: SYSTEM },
          { role: "user", content: userPrompt }
        ],
        temperature: 0.7,
        max_tokens: 8192
      })
    });
    txt = await res.text();
    json = JSON.parse(txt);
  } catch (e) {
    if (attempt < 2) {
      await new Promise(r => setTimeout(r, 2000));
      return callDeepSeek(userPrompt, attempt + 1);
    }
    return { error: `network: ${e.message}`, ms: Date.now() - t0 };
  }
  const ms = Date.now() - t0;
  if (!res.ok || json.error) {
    return { error: `api: ${json.error?.message || res.status}`, ms, raw: json };
  }
  const content = json.choices?.[0]?.message?.content || "";
  const usage = json.usage || {};
  return {
    ms,
    content,
    in: usage.prompt_tokens || 0,
    out: usage.completion_tokens || 0
  };
}

function stripThink(s) { return String(s).replace(/<think>[\s\S]*?<\/think>/g, "").trim(); }
function extractJson(content) {
  const s = stripThink(content);
  const fence = s.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = s.search(/[[]/);
  if (start === -1) return s;
  // find matching ] from end
  const end = s.lastIndexOf("]");
  return end > start ? s.slice(start, end + 1).trim() : s.slice(start).trim();
}

// --- Verify generated item structure ---
function verifyItem(item) {
  if (!item || typeof item !== "object") return { ok: false, reason: "not object" };
  const e = (item.expression || "").trim();
  const a = item.answer == null ? "" : String(item.answer).trim();
  if (!e) return { ok: false, reason: "empty expression" };
  if (!a) return { ok: false, reason: "empty answer" };
  // Reject Latex syntax (we asked for ASCII)
  if (/\\frac|\\cdot|\\geq|\\leq|\\sqrt/.test(e)) return { ok: false, reason: "contains LaTeX" };
  return { ok: true };
}

// --- Pick source samples from a zadanie ---
function pickSamples(zad) {
  // Prefer tasks without figures, with non-empty expression
  const candidates = (zad.tasks || []).filter(t =>
    t.expression && !t.src?.has_figure && t.expression.length < 200
  );
  // Shuffle and pick MAX_FEWSHOT
  const shuf = [...candidates].sort(() => Math.random() - 0.5);
  return shuf.slice(0, MAX_FEWSHOT).map(t => t.expression);
}

// --- Main loop ---
const targetZad = sourceBlock.zadaniya
  .filter(z => (z.tasks || []).filter(t => t.expression).length >= MIN_SOURCE)
  .sort((a, b) => (b.tasks?.length || 0) - (a.tasks?.length || 0))
  .slice(0, MAX_ZAD);

console.log(`Will process ${targetZad.length} zadaniya (≥${MIN_SOURCE} source examples each)\n`);

const generatedZadaniya = [];
let totalCost = 0, totalIn = 0, totalOut = 0, totalMs = 0;
let totalAccepted = 0, totalRejected = 0;

for (const z of targetZad) {
  const samples = pickSamples(z);
  if (samples.length < MIN_SOURCE) continue;
  process.stdout.write(`[№${z.number}] "${z.instruction.slice(0, 50)}" (${samples.length} samples) → `);

  const prompt = buildUserPrompt(z.instruction, samples, PER_ZAD);
  const r = await callDeepSeek(prompt);

  if (r.error) {
    console.log(`ERROR: ${r.error}`);
    continue;
  }

  let parsed;
  try { parsed = JSON.parse(extractJson(r.content)); }
  catch (e) { console.log(`PARSE ERR: ${e.message}`); continue; }
  if (!Array.isArray(parsed)) { console.log("not array"); continue; }

  // Validate items
  const accepted = [];
  const seen = new Set();
  for (const item of parsed) {
    const v = verifyItem(item);
    if (!v.ok) { totalRejected++; continue; }
    const key = (item.expression || "").replace(/\s+/g, "").toLowerCase();
    if (seen.has(key)) { totalRejected++; continue; }
    seen.add(key);
    accepted.push({
      id: accepted.length + 1,
      expression: item.expression.trim(),
      answer: String(item.answer).trim(),
      status: "generated",
      src: { generator: "deepseek-v4-pro", model_call_ms: r.ms }
    });
    totalAccepted++;
  }

  const cost = r.in * PRICE_IN + r.out * PRICE_OUT;
  totalCost += cost; totalIn += r.in; totalOut += r.out; totalMs += r.ms;

  console.log(`${accepted.length}/${parsed.length}  ${r.ms}ms  ${r.in}→${r.out}t  $${cost.toFixed(5)}`);

  if (accepted.length > 0) {
    generatedZadaniya.push({
      number: generatedZadaniya.length + 1,
      instruction: z.instruction,
      tasks: accepted,
      _meta: {
        based_on_zadanie: z.number,
        source_samples_used: samples.length
      }
    });
  }
}

// --- Save: replace block 2 if exists, else add ---
const newBlock = {
  number: 2,
  title: "Сгенерированные DeepSeek",
  generated_at: new Date().toISOString(),
  zadaniya: generatedZadaniya
};

const existingIdx = topic.blocks.findIndex(b => b.number === 2);
if (existingIdx >= 0) topic.blocks[existingIdx] = newBlock;
else topic.blocks.push(newBlock);

fs.writeFileSync(SRC_FILE, JSON.stringify(topic, null, 2));

console.log(`\n=== SUMMARY ===`);
console.log(`zadaniya generated: ${generatedZadaniya.length}`);
console.log(`tasks accepted: ${totalAccepted}  rejected: ${totalRejected}`);
console.log(`tokens: ${totalIn}→${totalOut}  total cost: $${totalCost.toFixed(5)}`);
console.log(`API time: ${totalMs}ms (${(totalMs/1000).toFixed(1)}s)`);
console.log(`saved → ${SRC_FILE}`);
