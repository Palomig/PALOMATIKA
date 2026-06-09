#!/usr/bin/env node
// Run with: node --env-file=.env scripts/ocr-makarychev.mjs
// OCR all rendered textbook pages via MiniMax /v1/coding_plan/vlm.
// Resumable: skips pages whose JSON already exists in OUT_DIR.
import fs from "node:fs";
import path from "node:path";

const KEY = process.env.MINIMAX_API_KEY;
if (!KEY) { console.error("MINIMAX_API_KEY missing"); process.exit(1); }

const HOST = process.env.MINIMAX_API_HOST || "https://api.minimax.io";
const ENDPOINT = `${HOST}/v1/coding_plan/vlm`;

const SRC_DIR = "/tmp/makarychev_pages";
const OUT_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/makarychev_pages";
const CONCURRENCY = 5;
const MAX_RETRIES = 2;
const RETRY_DELAY_MS = 4000;

fs.mkdirSync(OUT_DIR, { recursive: true });

// Chapter mapping from TOC
function chapterFor(pageNum) {
  if (pageNum >= 3 && pageNum <= 50) return { ch: "I", title: "Выражения, тождества, уравнения" };
  if (pageNum >= 51 && pageNum <= 94) return { ch: "II", title: "Функции" };
  if (pageNum >= 95 && pageNum <= 128) return { ch: "III", title: "Степень с натуральным показателем" };
  if (pageNum >= 129 && pageNum <= 164) return { ch: "IV", title: "Многочлены" };
  if (pageNum >= 165 && pageNum <= 200) return { ch: "V", title: "Формулы сокращённого умножения" };
  if (pageNum >= 201 && pageNum <= 240) return { ch: "VI", title: "Системы линейных уравнений" };
  return { ch: "?", title: "Приложения / справочный материал" };
}

// Section mapping (rough — for orientation)
function sectionFor(pageNum) {
  const sections = [
    [3, 21, "§1 Числа и выражения"],
    [22, 31, "§2 Преобразование выражений"],
    [32, 50, "§3 Уравнения с одной переменной"],
    [51, 68, "§4 Функции и их графики"],
    [69, 94, "§5 Линейная функция"],
    [95, 109, "§6 Степень и её свойства"],
    [110, 128, "§7 Одночлены"],
    [129, 136, "§8 Сумма и разность многочленов"],
    [137, 146, "§9 Произведение одночлена и многочлена"],
    [147, 164, "§10 Произведение многочленов"],
    [165, 173, "§11 Квадрат суммы и квадрат разности"],
    [174, 183, "§12 Разность квадратов, сумма и разность кубов"],
    [184, 200, "§13 Преобразование целых выражений"],
    [201, 212, "§14 Линейные уравнения с двумя переменными"],
    [213, 240, "§15 Решение систем линейных уравнений"]
  ];
  for (const [lo, hi, name] of sections) if (pageNum >= lo && pageNum <= hi) return name;
  return null;
}

const PROMPT = `Перед тобой страница из учебника алгебры 7 класса (Макарычев, 2023).
Извлеки ВСЕ задачи (упражнения) на странице в формате JSON-массива.
Если на странице нет задач (только теория, оглавление, ответы) — верни пустой массив [].

Поля каждой задачи:
- "number": номер задачи (целое число)
- "instruction": общая инструкция ("Решите уравнение", "Сравните числа", "Найдите значение" и т.п.) или null
- "subitems": массив подпунктов
  - "label": буква подпункта (а/б/в/г/...) или null
  - "expression": математическое выражение в ASCII (^, *, /, =), без LaTeX, дроби как "5/6", смешанные как "1 2/5", степени как "x^2"
  - "text": словесная часть условия, если есть
- "has_figure": true если есть рисунок/график

Формат ответа — СТРОГО валидный JSON-массив, без префиксов и комментариев.`;

function fileToDataUri(filePath) {
  const ext = path.extname(filePath).slice(1).toLowerCase();
  const fmt = ext === "jpg" ? "jpeg" : ext;
  const b64 = fs.readFileSync(filePath).toString("base64");
  return `data:image/${fmt};base64,${b64}`;
}

function stripThink(s) { return String(s).replace(/<think>[\s\S]*?<\/think>/g, "").trim(); }
function extractJson(content) {
  const s = stripThink(content);
  const fence = s.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = s.search(/[[{]/);
  return start === -1 ? s : s.slice(start).trim();
}

async function ocrOnePage(pageNum, attempt = 0) {
  const fname = String(pageNum).padStart(3, "0");
  const srcFile = path.join(SRC_DIR, `p-${fname}.png`);
  if (!fs.existsSync(srcFile)) return { pageNum, error: "PNG missing" };

  const t0 = Date.now();
  let res, txt, json;
  try {
    res = await fetch(ENDPOINT, {
      method: "POST",
      headers: { "Authorization": `Bearer ${KEY}`, "Content-Type": "application/json" },
      body: JSON.stringify({ prompt: PROMPT, image_url: fileToDataUri(srcFile) })
    });
    txt = await res.text();
    json = JSON.parse(txt);
  } catch (e) {
    if (attempt < MAX_RETRIES) {
      await new Promise(r => setTimeout(r, RETRY_DELAY_MS));
      return ocrOnePage(pageNum, attempt + 1);
    }
    return { pageNum, error: `network/parse: ${e.message}`, ms: Date.now() - t0 };
  }

  const ms = Date.now() - t0;

  if (json.base_resp && json.base_resp.status_code !== 0) {
    if (attempt < MAX_RETRIES) {
      await new Promise(r => setTimeout(r, RETRY_DELAY_MS));
      return ocrOnePage(pageNum, attempt + 1);
    }
    return { pageNum, error: `api ${json.base_resp.status_code}: ${json.base_resp.status_msg}`, ms };
  }

  const content = json.content ?? json.data?.content ?? "";
  let problems = null, parseErr = null;
  try { problems = JSON.parse(extractJson(content)); }
  catch (e) { parseErr = e.message; }

  if (!Array.isArray(problems)) {
    if (parseErr && attempt < MAX_RETRIES) {
      await new Promise(r => setTimeout(r, RETRY_DELAY_MS));
      return ocrOnePage(pageNum, attempt + 1);
    }
    return { pageNum, error: parseErr || "non-array result", ms, raw_head: content.slice(0, 500) };
  }

  return { pageNum, problems, ms };
}

async function processPage(pageNum) {
  const fname = String(pageNum).padStart(3, "0");
  const outFile = path.join(OUT_DIR, `p${fname}.json`);
  if (fs.existsSync(outFile)) return { pageNum, skipped: true };

  const r = await ocrOnePage(pageNum);
  const meta = chapterFor(pageNum);
  const sec = sectionFor(pageNum);
  const result = {
    page: pageNum,
    chapter: meta.ch,
    chapter_title: meta.title,
    section: sec,
    latency_ms: r.ms ?? null,
    error: r.error ?? null,
    problems: r.problems ?? [],
    raw_head: r.raw_head ?? null
  };
  fs.writeFileSync(outFile, JSON.stringify(result, null, 2));
  return { pageNum, count: r.problems?.length ?? 0, error: r.error, ms: r.ms };
}

async function runPool(items, conc, fn) {
  const results = [];
  let i = 0;
  const workers = Array.from({ length: conc }, async () => {
    while (i < items.length) {
      const idx = i++;
      const r = await fn(items[idx]);
      results[idx] = r;
      const tag = r.skipped ? "skip"
        : r.error ? `ERR(${r.error.slice(0, 40)})`
        : `${r.count} probs ${r.ms}ms`;
      console.log(`p${String(items[idx]).padStart(3, "0")}: ${tag}`);
    }
  });
  await Promise.all(workers);
  return results;
}

const PAGES = [];
for (let p = 3; p <= 252; p++) PAGES.push(p);

console.log(`OCR ${PAGES.length} pages with concurrency=${CONCURRENCY}`);
const t0 = Date.now();
const results = await runPool(PAGES, CONCURRENCY, processPage);
const elapsed = ((Date.now() - t0) / 1000).toFixed(1);

const stats = results.reduce((acc, r) => {
  if (r.skipped) acc.skipped++;
  else if (r.error) { acc.errors++; acc.errPages.push(r.pageNum); }
  else { acc.ok++; acc.problems += r.count; }
  return acc;
}, { ok: 0, skipped: 0, errors: 0, problems: 0, errPages: [] });

console.log(`\n==== DONE in ${elapsed}s ====`);
console.log(`ok: ${stats.ok}  skipped: ${stats.skipped}  errors: ${stats.errors}`);
console.log(`total problems extracted: ${stats.problems}`);
if (stats.errPages.length) console.log(`error pages: ${stats.errPages.join(", ")}`);
