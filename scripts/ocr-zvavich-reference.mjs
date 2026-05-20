#!/usr/bin/env node
// Build a non-verbatim reference index from the Zvavich grade 7 didactic materials.
//
// Run:
//   node --env-file=.env scripts/ocr-zvavich-reference.mjs --pages=35-36
//
// Required:
//   MINIMAX_API_KEY   MiniMax VLM OCR pass
//   DEEPSEEK_API_KEY  DeepSeek normalization pass
//
// Output is intentionally not a full textbook transcription. It stores task
// archetypes, skill tags, complexity markers, and generation notes so we can
// create original assignments without copying the source verbatim.
import fs from "node:fs";
import path from "node:path";
import { spawnSync } from "node:child_process";

const PDF_URL = process.env.ZVAVICH_PDF_URL
  || "https://ege-ok.ru/wp-content/uploads/2014/01/Algebra.-7kl.-Didaktich.-mater._Zvavich-L.I.-i-dr_2012-159s.pdf";
const WORK_DIR = process.env.ZVAVICH_WORK_DIR || "/tmp/palomatika_refs/zvavich";
const OUT_DIR = process.env.ZVAVICH_REF_OUT_DIR
  || "/home/dev/palomatika/storage/app/tasks/alg/grade_7/_ref/zvavich_reference_pages";
const PDF_PATH = path.join(WORK_DIR, "zvavich7.pdf");
const PAGE_DIR = path.join(WORK_DIR, "pages");
const SKILLS_PATH = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/skills.json";

const minimaxKey = process.env.MINIMAX_API_KEY;
const deepseekKey = process.env.DEEPSEEK_API_KEY;
const minimaxHost = process.env.MINIMAX_API_HOST || "https://api.minimax.io";
const minimaxEndpoint = process.env.MINIMAX_VLM_ENDPOINT || `${minimaxHost}/v1/coding_plan/vlm`;
const deepseekEndpoint = process.env.DEEPSEEK_API_ENDPOINT || "https://api.deepseek.com/chat/completions";
const deepseekModel = process.env.DEEPSEEK_MODEL || "deepseek-v4-flash";
const concurrency = Number(process.env.ZVAVICH_CONCURRENCY || 2);
const renderDpi = Number(process.env.ZVAVICH_RENDER_DPI || 180);
const dryRun = process.argv.includes("--dry-run");

const arg = (name) => {
  const prefix = `--${name}=`;
  return process.argv.find((item) => item.startsWith(prefix))?.slice(prefix.length);
};

const pagesArg = arg("pages") || process.env.ZVAVICH_PAGES || "1-159";

function parsePages(input) {
  const pages = new Set();
  for (const part of String(input).split(",")) {
    const trimmed = part.trim();
    if (!trimmed) continue;
    const range = trimmed.match(/^(\d+)-(\d+)$/);
    if (range) {
      const lo = Number(range[1]);
      const hi = Number(range[2]);
      for (let page = lo; page <= hi; page++) pages.add(page);
      continue;
    }
    const single = Number(trimmed);
    if (!Number.isInteger(single) || single < 1) throw new Error(`Bad page selector: ${trimmed}`);
    pages.add(single);
  }
  return [...pages].sort((a, b) => a - b);
}

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

async function downloadPdf() {
  if (fs.existsSync(PDF_PATH) && fs.statSync(PDF_PATH).size > 0) return;
  console.log(`download: ${PDF_URL}`);
  const res = await fetch(PDF_URL);
  if (!res.ok) throw new Error(`PDF download failed: HTTP ${res.status}`);
  const data = Buffer.from(await res.arrayBuffer());
  fs.writeFileSync(PDF_PATH, data);
}

function renderPages() {
  const marker = path.join(PAGE_DIR, ".rendered");
  if (fs.existsSync(marker)) return;
  ensureDir(PAGE_DIR);
  const prefix = path.join(PAGE_DIR, "p");
  const result = spawnSync("pdftoppm", ["-png", "-r", String(renderDpi), PDF_PATH, prefix], {
    encoding: "utf8",
  });
  if (result.status !== 0) {
    throw new Error(`pdftoppm failed: ${result.stderr || result.stdout}`);
  }
  fs.writeFileSync(marker, new Date().toISOString());
}

function pageImage(page) {
  const exact = path.join(PAGE_DIR, `p-${String(page).padStart(3, "0")}.png`);
  if (fs.existsSync(exact)) return exact;
  const fallback = path.join(PAGE_DIR, `p-${page}.png`);
  if (fs.existsSync(fallback)) return fallback;
  throw new Error(`Rendered image missing for page ${page}`);
}

function dataUri(filePath) {
  const b64 = fs.readFileSync(filePath).toString("base64");
  return `data:image/png;base64,${b64}`;
}

function knownSkills() {
  if (!fs.existsSync(SKILLS_PATH)) return [];
  const data = JSON.parse(fs.readFileSync(SKILLS_PATH, "utf8"));
  return data.skills.map((skill) => ({
    id: skill.id,
    slug: skill.slug,
    title: skill.title,
    task_type: skill.task_type,
  }));
}

const skills = knownSkills();
const skillList = skills.map((skill) => `${skill.id}: ${skill.title}`).join("\n");

const minimaxPrompt = `Ты анализируешь ОДНУ страницу сканированных дидактических материалов по алгебре 7 класса.

Нужно сделать НЕ дословную оцифровку, а референс-карточки для генерации оригинальных заданий.

Правила:
- НЕ переписывай полный текст условий и подпунктов из учебника.
- НЕ сохраняй длинные фразы из источника.
- Можно указывать номер задания, количество подпунктов, математический шаблон без конкретных чисел, навык, уровень сложности и краткую идею.
- Если нужно показать форму, используй абстрактные шаблоны: "a(x+b)=c", "(ax+b)+(cx+d)", "a^2-b^2", "найти пропущенный одночлен".
- Верни строго валидный JSON-объект.

Схема:
{
  "page": number,
  "problems": [
    {
      "source_number": number | string | null,
      "subitems_count": number,
      "instruction_paraphrase": string | null,
      "math_archetype": string,
      "skill_tags": string[],
      "difficulty_hint": "simple" | "medium" | "high",
      "complexity_markers": string[],
      "has_figure": boolean,
      "notes_for_original_generation": string
    }
  ]
}`;

function cleanJsonText(content) {
  const stripped = String(content ?? "").replace(/<think>[\s\S]*?<\/think>/g, "").trim();
  const fence = stripped.match(/```(?:json)?\s*([\s\S]+?)```/);
  if (fence) return fence[1].trim();
  const start = stripped.search(/[{\[]/);
  return start >= 0 ? stripped.slice(start).trim() : stripped;
}

function parseModelJson(content) {
  const text = cleanJsonText(content);
  try {
    return JSON.parse(text);
  } catch (error) {
    // Models sometimes emit math snippets such as \cdot or \begin inside JSON
    // strings. JSON only allows a small set of escaped characters, so escape
    // stray backslashes and try once more.
    const repaired = text.replace(/\\(?!["\\/bfnrtu])/g, "\\\\");
    try {
      return JSON.parse(repaired);
    } catch {
      throw error;
    }
  }
}

async function minimaxPage(page) {
  const res = await fetch(minimaxEndpoint, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${minimaxKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      prompt: `${minimaxPrompt}\n\nНомер страницы PDF: ${page}`,
      image_url: dataUri(pageImage(page)),
    }),
  });

  const body = await res.text();
  if (!res.ok) throw new Error(`MiniMax HTTP ${res.status}: ${body.slice(0, 240)}`);
  const json = JSON.parse(body);
  if (json.base_resp && json.base_resp.status_code !== 0) {
    throw new Error(`MiniMax API ${json.base_resp.status_code}: ${json.base_resp.status_msg}`);
  }
  const content = json.content ?? json.data?.content ?? json.choices?.[0]?.message?.content ?? "";
  return parseModelJson(content);
}

async function deepseekNormalize(page, pageRef) {
  const system = "Ты нормализуешь OCR-референсы учебных заданий в строгий JSON. Не добавляй дословный текст из источника.";
  const user = `Ниже JSON от OCR по странице дидактических материалов.

Задача:
1. Исправь JSON, если есть мелкие ошибки.
2. Привяжи каждый элемент к ближайшему навыку из списка, если возможно.
3. Сохрани НЕ дословный характер: только архетипы, навыки, параметры сложности, идеи для генерации оригинальных задач.
4. Верни строго JSON-объект по схеме:
{
  "page": ${page},
  "source": "Звавич Л.И. и др., дидактические материалы, алгебра 7",
  "copyright_mode": "non_verbatim_reference",
  "problems": [
    {
      "source_number": number | string | null,
      "subitems_count": number,
      "instruction_paraphrase": string | null,
      "math_archetype": string,
      "skill_ids": string[],
      "difficulty": "simple" | "medium" | "high",
      "complexity_markers": string[],
      "has_figure": boolean,
      "generation_recipe": string
    }
  ]
}

Список навыков текущего банка:
${skillList}

OCR JSON:
${JSON.stringify(pageRef)}`;

  const res = await fetch(deepseekEndpoint, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${deepseekKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      model: deepseekModel,
      messages: [
        { role: "system", content: system },
        { role: "user", content: user },
      ],
      temperature: 0,
      stream: false,
      response_format: { type: "json_object" },
    }),
  });

  const body = await res.text();
  if (!res.ok) throw new Error(`DeepSeek HTTP ${res.status}: ${body.slice(0, 240)}`);
  const json = JSON.parse(body);
  const content = json.choices?.[0]?.message?.content ?? "";
  return parseModelJson(content);
}

async function processPage(page) {
  const outFile = path.join(OUT_DIR, `p${String(page).padStart(3, "0")}.json`);
  if (fs.existsSync(outFile)) return { page, skipped: true };

  const t0 = Date.now();
  const firstPass = await minimaxPage(page);
  const normalized = await deepseekNormalize(page, firstPass);
  const safe = {
    ...normalized,
    page,
    generated_at: new Date().toISOString(),
    pipeline: {
      minimax_endpoint: minimaxEndpoint.replace(/\/\/[^/]+/, "//api.minimax.io"),
      deepseek_endpoint: deepseekEndpoint.replace(/\/\/[^/]+/, "//api.deepseek.com"),
      deepseek_model: deepseekModel,
    },
  };
  fs.writeFileSync(outFile, JSON.stringify(safe, null, 2));
  return { page, count: safe.problems?.length ?? 0, ms: Date.now() - t0 };
}

async function runPool(items, limit, fn) {
  const results = [];
  let cursor = 0;
  const workers = Array.from({ length: Math.min(limit, items.length) }, async () => {
    while (cursor < items.length) {
      const index = cursor++;
      const item = items[index];
      try {
        const result = await fn(item);
        results[index] = result;
        const tag = result.skipped ? "skip" : `${result.count} refs ${result.ms}ms`;
        console.log(`p${String(item).padStart(3, "0")}: ${tag}`);
      } catch (error) {
        results[index] = { page: item, error: error.message };
        console.log(`p${String(item).padStart(3, "0")}: ERR ${error.message}`);
      }
    }
  });
  await Promise.all(workers);
  return results;
}

async function main() {
  const pages = parsePages(pagesArg);
  console.log(`Zvavich reference OCR: pages=${pagesArg}, count=${pages.length}`);
  console.log(`out: ${OUT_DIR}`);

  if (dryRun) {
    console.log(`dry-run: minimax key ${minimaxKey ? "set" : "missing"}, deepseek key ${deepseekKey ? "set" : "missing"}`);
    console.log(`dry-run: pdf ${PDF_PATH}`);
    return;
  }

  if (!minimaxKey) throw new Error("MINIMAX_API_KEY missing");
  if (!deepseekKey) throw new Error("DEEPSEEK_API_KEY missing");

  ensureDir(WORK_DIR);
  ensureDir(OUT_DIR);
  await downloadPdf();
  renderPages();

  const results = await runPool(pages, concurrency, processPage);
  const errors = results.filter((item) => item.error);
  const extracted = results.reduce((sum, item) => sum + (item.count ?? 0), 0);
  console.log(`done: refs=${extracted}, errors=${errors.length}`);
  if (errors.length) {
    console.log(`error pages: ${errors.map((item) => item.page).join(", ")}`);
    process.exitCode = 1;
  }
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
