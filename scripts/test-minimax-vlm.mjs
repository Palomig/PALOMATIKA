#!/usr/bin/env node
// Run with: node --env-file=.env scripts/test-minimax-vlm.mjs
// Tests MiniMax /v1/coding_plan/vlm endpoint (image understanding via Token Plan).
import fs from "node:fs";
import path from "node:path";

const KEY = process.env.MINIMAX_API_KEY;
if (!KEY) { console.error("MINIMAX_API_KEY missing"); process.exit(1); }

const HOST = process.env.MINIMAX_API_HOST || "https://api.minimax.io";
const ENDPOINT = `${HOST}/v1/coding_plan/vlm`;

const PROMPT = `Перед тобой страница из учебника алгебры 7 класса (Макарычев, 2023).
Извлеки ВСЕ задачи на странице в формате JSON-массива. Каждая задача — отдельный объект.

Поля:
- "number": номер задачи (целое число)
- "instruction": общая инструкция к задаче ("Решите уравнение", "Сравните числа" и т.п.) или null
- "subitems": массив подпунктов (а, б, в, г); если задача без подпунктов — массив с одним элементом
  - "label": буква подпункта или null
  - "expression": математическое выражение в ASCII (^, *, /, =, без LaTeX)
  - "text": текстовая часть условия (если есть)
- "has_figure": true если у задачи есть рисунок/график

Формат ответа — СТРОГО валидный JSON-массив, БЕЗ префиксов и комментариев.
Дроби: "5/6", смешанные: "1 2/5", степени: "x^2".`;

const PAGES = [
  { num: 10, file: "/tmp/textbook-sample/p10-010.png", note: "Гл.I задачи 2–9" },
  { num: 50, file: "/tmp/textbook-sample/p50-050.png", note: "Гл.I задачи 237–246" },
  { num: 100, file: "/tmp/textbook-sample/p100-100.png", note: "Гл.III задачи 407–411 + рис." }
];

function fileToDataUri(filePath) {
  const ext = path.extname(filePath).slice(1).toLowerCase();
  const fmt = ext === "jpg" ? "jpeg" : ext;
  const b64 = fs.readFileSync(filePath).toString("base64");
  return `data:image/${fmt};base64,${b64}`;
}

async function understand(imagePath, prompt) {
  const t0 = Date.now();
  const res = await fetch(ENDPOINT, {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${KEY}`,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      prompt,
      image_url: fileToDataUri(imagePath)
    })
  });
  const ms = Date.now() - t0;
  const txt = await res.text();
  let json = null;
  try { json = JSON.parse(txt); } catch { /* keep txt */ }
  return { ms, status: res.status, json, raw: txt };
}

function extractJson(content) {
  if (!content) return "";
  const stripped = String(content).replace(/<think>[\s\S]*?<\/think>/g, "").trim();
  const fence = stripped.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = stripped.search(/[[{]/);
  if (start === -1) return stripped;
  return stripped.slice(start).trim();
}

const report = [];
for (const page of PAGES) {
  process.stdout.write(`\n[p${page.num}] ${page.note}\n`);
  let r;
  try { r = await understand(page.file, PROMPT); }
  catch (e) { console.log(`  ERROR: ${e.message}`); report.push({ page: page.num, error: e.message }); continue; }

  console.log(`  http: ${r.status}  latency: ${r.ms}ms`);
  if (r.status !== 200 || !r.json) {
    console.log(`  body (first 500): ${r.raw.slice(0, 500)}`);
    report.push({ page: page.num, status: r.status, error: r.raw.slice(0, 500) });
    continue;
  }

  // Check for API-level error
  if (r.json.base_resp && r.json.base_resp.status_code !== 0) {
    console.log(`  API ERROR: ${r.json.base_resp.status_code} ${r.json.base_resp.status_msg}`);
    report.push({ page: page.num, error: `api ${r.json.base_resp.status_code}: ${r.json.base_resp.status_msg}` });
    continue;
  }

  // Try multiple shapes for "content"
  const content =
    r.json.content ??
    r.json.data?.content ??
    r.json.choices?.[0]?.message?.content ??
    r.json.result ??
    "";

  let parsed = null, parseErr = null;
  try { parsed = JSON.parse(extractJson(content)); }
  catch (e) { parseErr = e.message; }

  if (parsed && Array.isArray(parsed)) {
    console.log(`  JSON: ${parsed.length} problems`);
    console.log(`  numbers: ${parsed.map(p => p.number).join(", ")}`);
    if (parsed[0]) {
      const sample = parsed[0];
      console.log(`  sample #${sample.number} — ${sample.instruction ?? "(no instruction)"}`);
      const sub = sample.subitems?.[0];
      if (sub) console.log(`    [${sub.label ?? "—"}] ${(sub.expression || sub.text || "").slice(0, 120)}`);
    }
  } else {
    console.log(`  parse: ${parseErr || "non-array"}`);
    console.log(`  content head: ${String(content).slice(0, 400)}`);
  }

  report.push({
    page: page.num,
    latency_ms: r.ms,
    json_keys: Object.keys(r.json),
    problems_count: parsed?.length ?? 0,
    sample_problems: parsed?.slice(0, 3) ?? null,
    raw_content_head: String(content).slice(0, 800),
    parse_error: parseErr
  });
}

const outPath = path.resolve("scripts", "minimax-vlm-report.json");
fs.writeFileSync(outPath, JSON.stringify(report, null, 2));
console.log(`\nfull report: ${outPath}`);
