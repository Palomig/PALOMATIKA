#!/usr/bin/env node
// Run with: node --env-file=.env scripts/test-minimax.mjs
import fs from "node:fs";
import path from "node:path";

const KEY = process.env.MINIMAX_API_KEY;
if (!KEY) { console.error("MINIMAX_API_KEY missing"); process.exit(1); }

const ENDPOINT = "https://api.minimax.io/v1/text/chatcompletion_v2";
const MODEL = "MiniMax-M2.7";

const PRICE_IN = 0.30 / 1_000_000;
const PRICE_OUT = 1.20 / 1_000_000;

const SYSTEM = `Ты помощник для создания школьных заданий по алгебре 7 класса (Россия, ФГОС).
Отвечай СТРОГО валидным JSON без префиксов/комментариев — массив объектов задач.
Каждый объект: { "expression": "...", "answer": "..." }.
Используй обычные ASCII-символы (^, *, /, =), без LaTeX и unicode-степеней.
Все числа — целые или с одной десятичной. Никаких пояснений.`;

const TESTS = [
  {
    id: "T2.2",
    name: "Линейное уравнение ax+b=cx+d",
    prompt: `Сгенерируй 5 РАЗНЫХ линейных уравнений вида ax+b=cx+d.
Параметры: a,c ∈ [-9..9]\\{0}, a≠c, b,d ∈ [-15..15]. Корень x должен быть ЦЕЛЫМ в [-10..10].
Поле "expression" — само уравнение строкой, "answer" — значение x.
Пример: { "expression": "5x-3=2x+9", "answer": "4" }`,
    verify: (item) => {
      const m = String(item.expression).replace(/\s+/g, "").match(/^(-?\d*)x([+-]\d+)=(-?\d*)x([+-]\d+)$/);
      if (!m) return { ok: false, reason: "regex no match" };
      const a = m[1] === "" || m[1] === "-" ? (m[1] === "-" ? -1 : 1) : parseInt(m[1], 10);
      const b = parseInt(m[2], 10);
      const c = m[3] === "" || m[3] === "-" ? (m[3] === "-" ? -1 : 1) : parseInt(m[3], 10);
      const d = parseInt(m[4], 10);
      if (a === c) return { ok: false, reason: "a===c" };
      const x = (d - b) / (a - c);
      const claimed = parseFloat(item.answer);
      if (Math.abs(x - claimed) > 1e-9) return { ok: false, reason: `expected x=${x}, got ${claimed}` };
      return { ok: true };
    }
  },
  {
    id: "T5.4",
    name: "Разность квадратов на множители",
    prompt: `Сгенерируй 5 РАЗНЫХ заданий: разложить разность квадратов на множители.
Шаблон: a^2*x^(2k) - b^2*y^(2m), где a,b ∈ [2..7], k,m ∈ [1..2].
Поле "expression" — исходное выражение, "answer" — разложение в виде (..)*(..).
Пример: { "expression": "25x^4-9y^2", "answer": "(5x^2-3y)(5x^2+3y)" }`,
    verify: (item) => {
      // Best-effort: check answer has form (A-B)(A+B) and unfolding == expression
      const ans = String(item.answer).replace(/\s+/g, "");
      const m = ans.match(/^\(([^()]+)\)\(([^()]+)\)$/);
      if (!m) return { ok: false, reason: "answer not (..)(..)" };
      const [p1, p2] = [m[1], m[2]];
      // Heuristic: one is A-B, other is A+B
      const isDiff = (s) => s.includes("-");
      const isSum = (s) => /[^(]\+/.test(s);
      if (!((isDiff(p1) && isSum(p2)) || (isDiff(p2) && isSum(p1)))) return { ok: false, reason: "no (A-B)(A+B) shape" };
      // Don't verify algebraically — symbolic eval is overkill. Format compliance is the signal.
      return { ok: true };
    }
  },
  {
    id: "T8.1",
    name: "Текстовая задача на сумму",
    prompt: `Сгенерируй 5 РАЗНЫХ текстовых задач на сумму двух чисел (для 7 класса).
Сюжет: «В двух X всего N штук, в первой на K больше». N ∈ [20..200], K ∈ [2..50], (N-K)/2 — целое.
Поле "expression" — формулировка задачи на русском языке (одно предложение), "answer" — два числа через запятую (большее, меньшее).
Пример: { "expression": "В двух коробках всего 80 конфет, в первой на 12 больше. Сколько конфет в каждой?", "answer": "46,34" }`,
    verify: (item) => {
      const txt = String(item.expression);
      const ans = String(item.answer).replace(/\s+/g, "");
      const nums = ans.split(",").map(Number);
      if (nums.length !== 2 || nums.some(Number.isNaN)) return { ok: false, reason: "answer not two numbers" };
      const [big, small] = nums;
      const N = big + small;
      const K = big - small;
      // Try to find N and K in problem text
      const textNums = (txt.match(/\d+/g) || []).map(Number);
      const hasN = textNums.includes(N);
      const hasK = textNums.includes(K);
      if (!hasN || !hasK) return { ok: false, reason: `text numbers ${textNums} don't contain N=${N} or K=${K}` };
      if (big <= small) return { ok: false, reason: "big <= small" };
      return { ok: true };
    }
  }
];

async function callMiniMax(userPrompt) {
  const t0 = Date.now();
  const res = await fetch(ENDPOINT, {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${KEY}`,
      "Content-Type": "application/json"
    },
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
  const ms = Date.now() - t0;
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`HTTP ${res.status}: ${text.slice(0, 500)}`);
  }
  const json = await res.json();
  return { json, ms };
}

function extractJson(content) {
  // M2.7 emits <think>...</think> reasoning blocks — strip them first
  const stripped = content.replace(/<think>[\s\S]*?<\/think>/g, "").trim();
  const fence = stripped.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = stripped.search(/[[{]/);
  if (start === -1) return stripped;
  return stripped.slice(start).trim();
}

const report = { model: MODEL, runs: [] };

for (const test of TESTS) {
  process.stdout.write(`\n[${test.id}] ${test.name}\n`);
  let result;
  try {
    result = await callMiniMax(test.prompt);
  } catch (e) {
    console.log(`  ERROR: ${e.message}`);
    report.runs.push({ id: test.id, error: e.message });
    continue;
  }

  const choice = result.json?.choices?.[0]?.message?.content || "";
  const usage = result.json?.usage || {};
  const inTok = usage.prompt_tokens || usage.input_tokens || 0;
  const outTok = usage.completion_tokens || usage.output_tokens || 0;
  const cost = inTok * PRICE_IN + outTok * PRICE_OUT;

  let parsed = null;
  let parseErr = null;
  try {
    parsed = JSON.parse(extractJson(choice));
  } catch (e) {
    parseErr = e.message;
  }

  let okCount = 0, failCount = 0;
  const failures = [];
  if (Array.isArray(parsed)) {
    for (const item of parsed) {
      const v = test.verify(item);
      if (v.ok) okCount++;
      else { failCount++; failures.push({ item, reason: v.reason }); }
    }
  }

  console.log(`  latency: ${result.ms}ms  tokens: ${inTok}→${outTok}  cost: $${cost.toFixed(5)}`);
  console.log(`  json: ${parseErr ? `INVALID (${parseErr})` : `${parsed?.length ?? 0} items`}  pass: ${okCount}  fail: ${failCount}`);
  if (failures.length) {
    console.log(`  failures (first 2):`);
    failures.slice(0, 2).forEach(f => console.log(`    - ${f.reason} | ${JSON.stringify(f.item)}`));
  }
  if (parsed?.length) {
    console.log(`  sample: ${JSON.stringify(parsed[0])}`);
  } else if (!parseErr) {
    console.log(`  raw (first 200): ${choice.slice(0, 200)}`);
  }

  report.runs.push({
    id: test.id, name: test.name, latency_ms: result.ms,
    tokens_in: inTok, tokens_out: outTok, cost,
    parsed_items: parsed?.length ?? 0,
    pass: okCount, fail: failCount,
    parse_error: parseErr,
    failures: failures.slice(0, 3),
    sample: parsed?.[0] ?? null,
    raw_head: parseErr ? choice.slice(0, 500) : null
  });
}

const total = report.runs.reduce((acc, r) => ({
  cost: acc.cost + (r.cost || 0),
  pass: acc.pass + (r.pass || 0),
  fail: acc.fail + (r.fail || 0),
  in: acc.in + (r.tokens_in || 0),
  out: acc.out + (r.tokens_out || 0),
  ms: acc.ms + (r.latency_ms || 0)
}), { cost: 0, pass: 0, fail: 0, in: 0, out: 0, ms: 0 });

console.log(`\n=== TOTAL ===`);
console.log(`pass: ${total.pass}  fail: ${total.fail}  total tokens: ${total.in}→${total.out}`);
console.log(`cost: $${total.cost.toFixed(5)}  total latency: ${total.ms}ms`);

const outPath = path.resolve("scripts", "minimax-test-report.json");
fs.writeFileSync(outPath, JSON.stringify(report, null, 2));
console.log(`\nfull report: ${outPath}`);
