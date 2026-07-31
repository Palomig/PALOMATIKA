#!/usr/bin/env node
// Run with: node --env-file=.env scripts/test-gen-ab.mjs
// A/B test: same prompts on DeepSeek V4 Pro vs MiniMax M2.7
import fs from "node:fs";
import path from "node:path";

const MM_KEY = process.env.MINIMAX_API_KEY;
const DS_KEY = process.env.DEEPSEEK_API_KEY;
if (!MM_KEY || !DS_KEY) { console.error("missing key(s)"); process.exit(1); }

const MM_ENDPOINT = "https://api.minimax.io/v1/text/chatcompletion_v2";
const DS_ENDPOINT = "https://api.deepseek.com/v1/chat/completions";

// --- Pricing (per token) ---
const PRICES = {
  "MiniMax-M2.7":      { in: 0.30 / 1e6, out: 1.20 / 1e6 },
  "deepseek-v4-pro":   { in: 0.435 / 1e6, out: 0.87 / 1e6 }   // promo until 2026-05-31
};

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
Поле "expression" — само уравнение, "answer" — значение x.
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
"expression" — выражение, "answer" — разложение в виде (..)*(..).
Пример: { "expression": "25x^4-9y^2", "answer": "(5x^2-3y)(5x^2+3y)" }`,
    verify: (item) => {
      const ans = String(item.answer).replace(/\s+/g, "");
      const m = ans.match(/^\(([^()]+)\)\(([^()]+)\)$/);
      if (!m) return { ok: false, reason: "answer not (..)(..)" };
      const [p1, p2] = [m[1], m[2]];
      const isDiff = (s) => s.includes("-");
      const isSum = (s) => /[^(]\+/.test(s);
      if (!((isDiff(p1) && isSum(p2)) || (isDiff(p2) && isSum(p1)))) return { ok: false, reason: "no (A-B)(A+B) shape" };
      return { ok: true };
    }
  },
  {
    id: "T8.1",
    name: "Текстовая задача на сумму",
    prompt: `Сгенерируй 5 РАЗНЫХ текстовых задач на сумму двух чисел (для 7 класса).
Сюжет: «В двух X всего N штук, в первой на K больше». N ∈ [20..200], K ∈ [2..50], (N-K)/2 — целое.
"expression" — формулировка задачи на русском (одно предложение), "answer" — два числа через запятую (большее, меньшее).
Пример: { "expression": "В двух коробках всего 80 конфет, в первой на 12 больше. Сколько конфет в каждой?", "answer": "46,34" }`,
    verify: (item) => {
      const txt = String(item.expression);
      const ans = String(item.answer).replace(/\s+/g, "");
      const nums = ans.split(",").map(Number);
      if (nums.length !== 2 || nums.some(Number.isNaN)) return { ok: false, reason: "answer not two numbers" };
      const [big, small] = nums;
      const N = big + small;
      const K = big - small;
      const textNums = (txt.match(/\d+/g) || []).map(Number);
      if (!textNums.includes(N) || !textNums.includes(K)) return { ok: false, reason: `text nums ${textNums} miss N=${N}/K=${K}` };
      if (big <= small) return { ok: false, reason: "big <= small" };
      return { ok: true };
    }
  }
];

function stripThink(s) { return String(s).replace(/<think>[\s\S]*?<\/think>/g, "").trim(); }
function extractJson(content) {
  const stripped = stripThink(content);
  const fence = stripped.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = stripped.search(/[[{]/);
  if (start === -1) return stripped;
  return stripped.slice(start).trim();
}

async function callMiniMax(userPrompt) {
  const t0 = Date.now();
  const res = await fetch(MM_ENDPOINT, {
    method: "POST",
    headers: { "Authorization": `Bearer ${MM_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({
      model: "MiniMax-M2.7",
      messages: [{ role: "system", content: SYSTEM }, { role: "user", content: userPrompt }],
      temperature: 0.7,
      max_tokens: 8192
    })
  });
  const ms = Date.now() - t0;
  const json = await res.json();
  return {
    ms,
    content: json?.choices?.[0]?.message?.content || "",
    in: json?.usage?.prompt_tokens || 0,
    out: json?.usage?.completion_tokens || 0
  };
}

async function callDeepSeek(userPrompt) {
  const t0 = Date.now();
  const res = await fetch(DS_ENDPOINT, {
    method: "POST",
    headers: { "Authorization": `Bearer ${DS_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({
      model: "deepseek-v4-pro",
      messages: [{ role: "system", content: SYSTEM }, { role: "user", content: userPrompt }],
      temperature: 0.7,
      max_tokens: 8192
    })
  });
  const ms = Date.now() - t0;
  const json = await res.json();
  return {
    ms,
    content: json?.choices?.[0]?.message?.content || "",
    in: json?.usage?.prompt_tokens || 0,
    out: json?.usage?.completion_tokens || 0,
    raw: json
  };
}

const PROVIDERS = [
  { name: "MiniMax-M2.7", call: callMiniMax },
  { name: "deepseek-v4-pro", call: callDeepSeek }
];

const summary = {};
for (const p of PROVIDERS) summary[p.name] = { pass: 0, fail: 0, in: 0, out: 0, ms: 0, cost: 0 };

const detail = [];
for (const test of TESTS) {
  console.log(`\n==== ${test.id} — ${test.name} ====`);
  for (const p of PROVIDERS) {
    process.stdout.write(`  [${p.name}] `);
    let r;
    try { r = await p.call(test.prompt); }
    catch (e) { console.log(`ERROR: ${e.message}`); detail.push({ test: test.id, provider: p.name, error: e.message }); continue; }

    const price = PRICES[p.name];
    const cost = r.in * price.in + r.out * price.out;
    let parsed = null, parseErr = null;
    try { parsed = JSON.parse(extractJson(r.content)); } catch (e) { parseErr = e.message; }

    let ok = 0, bad = 0;
    if (Array.isArray(parsed)) {
      for (const item of parsed) { (test.verify(item).ok ? ok++ : bad++); }
    } else { bad = 5; }

    summary[p.name].pass += ok;
    summary[p.name].fail += bad;
    summary[p.name].in += r.in;
    summary[p.name].out += r.out;
    summary[p.name].ms += r.ms;
    summary[p.name].cost += cost;

    console.log(`pass: ${ok}/${ok+bad}  ${r.ms}ms  ${r.in}→${r.out}t  $${cost.toFixed(5)}`);
    detail.push({
      test: test.id, provider: p.name, latency_ms: r.ms,
      in: r.in, out: r.out, cost,
      parsed_len: parsed?.length ?? null, pass: ok, fail: bad,
      parse_error: parseErr,
      sample: parsed?.[0] ?? null,
      content_head: parseErr ? stripThink(r.content).slice(0, 400) : null
    });
  }
}

console.log(`\n==== SUMMARY ====`);
const fmt = (n, w=8) => String(n).padStart(w);
console.log(`provider          pass/total  total ms  in tok  out tok  cost`);
for (const p of PROVIDERS) {
  const s = summary[p.name];
  const total = s.pass + s.fail;
  console.log(`${p.name.padEnd(18)} ${fmt(s.pass + "/" + total, 9)}  ${fmt(s.ms)}  ${fmt(s.in)}  ${fmt(s.out)}  $${s.cost.toFixed(5)}`);
}

const outPath = path.resolve("scripts", "gen-ab-report.json");
fs.writeFileSync(outPath, JSON.stringify({ summary, detail }, null, 2));
console.log(`\nfull report: ${outPath}`);
