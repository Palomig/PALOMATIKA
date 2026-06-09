#!/usr/bin/env node
// Run with: node --env-file=.env scripts/test-minimax-vision.mjs
import fs from "node:fs";
import path from "node:path";

const KEY = process.env.MINIMAX_API_KEY;
if (!KEY) { console.error("MINIMAX_API_KEY missing"); process.exit(1); }

const ENDPOINT = "https://api.minimax.io/v1/text/chatcompletion_v2";

const BASE = "http://78.17.28.40/textbook-sample";
const PAGES = [
  { num: 10, url: `${BASE}/p10-010.png`, note: "Гл.I, задачи 2–9 (дроби, сравнения)" },
  { num: 50, url: `${BASE}/p50-050.png`, note: "Гл.I, задачи 237–246 (уравнения, текстовые)" },
  { num: 100, url: `${BASE}/p100-100.png`, note: "Гл.III, задачи 407–411 (с рисунками)" }
];

const PROMPT = `Перед тобой страница из учебника алгебры 7 класса (Макарычев, 2023).
Извлеки ВСЕ задачи на странице в формате JSON-массива. Каждая задача — отдельный объект.

Поля:
- "number": номер задачи (целое число)
- "subitems": массив подпунктов (а, б, в, г и т.д.); если задача без подпунктов — массив с одним объектом
  - "label": буква подпункта или null
  - "expression": математическое выражение/уравнение в ASCII (используй ^, *, /, =, без LaTeX и unicode-степеней)
  - "text": текстовая часть условия (если есть)
- "instruction": общая инструкция к задаче (например "Решите уравнение", "Сравните числа") — null если нет

Формат ответа — СТРОГО валидный JSON-массив, БЕЗ префиксов и комментариев.
Если на странице есть рисунки — упомяни в поле "has_figure": true.
Дроби пиши как "5/6", смешанные числа как "1 2/5", степени как "x^2".`;

const PRICE_IN = 0.30 / 1_000_000;
const PRICE_OUT = 1.20 / 1_000_000;

async function tryModel(model, page) {
  const t0 = Date.now();
  const res = await fetch(ENDPOINT, {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${KEY}`,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      model,
      messages: [
        { role: "system", content: "Ты OCR-помощник для учебников по математике. Возвращаешь СТРОГО валидный JSON." },
        {
          role: "user",
          content: [
            { type: "text", text: PROMPT },
            { type: "image_url", image_url: { url: page.url } }
          ]
        }
      ],
      temperature: 0.1,
      max_tokens: 8192
    })
  });
  const ms = Date.now() - t0;
  const txt = await res.text();
  if (!res.ok) {
    return { model, page: page.num, ms, error: `HTTP ${res.status}`, body: txt.slice(0, 500) };
  }
  let json;
  try { json = JSON.parse(txt); } catch { return { model, page: page.num, ms, error: "non-JSON response", body: txt.slice(0, 500) }; }
  // Surface API-level error codes
  if (json.base_resp && json.base_resp.status_code !== 0) {
    return { model, page: page.num, ms, error: `api ${json.base_resp.status_code}: ${json.base_resp.status_msg}`, json };
  }
  return { model, page: page.num, ms, json };
}

function stripThink(s) {
  return String(s).replace(/<think>[\s\S]*?<\/think>/g, "").trim();
}

function extractJson(content) {
  const stripped = stripThink(content);
  const fence = stripped.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = stripped.search(/[[{]/);
  if (start === -1) return stripped;
  return stripped.slice(start).trim();
}

const MODELS = ["MiniMax-VL-01"];
const report = [];

for (const model of MODELS) {
  for (const page of PAGES) {
    process.stdout.write(`\n[${model}] page ${page.num} — ${page.note}\n`);
    const r = await tryModel(model, page);
    if (r.error) {
      console.log(`  ERROR: ${r.error}`);
      console.log(`  body: ${r.body}`);
      report.push({ model, page: page.num, error: r.error, body: r.body });
      continue;
    }
    const choice = r.json?.choices?.[0]?.message?.content || "";
    const usage = r.json?.usage || {};
    const inTok = usage.prompt_tokens || usage.input_tokens || 0;
    const outTok = usage.completion_tokens || usage.output_tokens || 0;
    const cost = inTok * PRICE_IN + outTok * PRICE_OUT;

    let parsed = null, parseErr = null;
    try { parsed = JSON.parse(extractJson(choice)); } catch (e) { parseErr = e.message; }

    console.log(`  latency: ${r.ms}ms  tokens: ${inTok}→${outTok}  cost: $${cost.toFixed(5)}`);
    console.log(`  json: ${parseErr ? `INVALID (${parseErr})` : `${parsed?.length ?? 0} problems extracted`}`);
    if (Array.isArray(parsed) && parsed.length) {
      console.log(`  numbers: ${parsed.map(p => p.number).join(", ")}`);
      const sample = parsed[0];
      console.log(`  sample #${sample.number}:`);
      console.log(`    instruction: ${sample.instruction ?? "—"}`);
      const sub = sample.subitems?.[0];
      if (sub) console.log(`    [${sub.label ?? "—"}] ${(sub.expression ?? sub.text ?? "").slice(0, 100)}`);
    } else if (!parseErr) {
      console.log(`  raw (first 300): ${stripThink(choice).slice(0, 300)}`);
    }

    report.push({
      model, page: page.num, latency_ms: r.ms,
      tokens_in: inTok, tokens_out: outTok, cost,
      problems_count: parsed?.length ?? 0,
      numbers: Array.isArray(parsed) ? parsed.map(p => p.number) : null,
      parse_error: parseErr,
      sample: Array.isArray(parsed) ? parsed.slice(0, 2) : null,
      raw_head: parseErr ? stripThink(choice).slice(0, 800) : null
    });
  }
}

const outPath = path.resolve("scripts", "minimax-vision-report.json");
fs.writeFileSync(outPath, JSON.stringify(report, null, 2));
console.log(`\nfull report: ${outPath}`);
