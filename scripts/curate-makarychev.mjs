#!/usr/bin/env node
// Curate Makarychev problems → only MUST topics from tutor roadmap.
// Adds "topic_group" tag aligned with our T-template catalog.
import fs from "node:fs";
import path from "node:path";

const SRC = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_index.json";
const OUT_CURATED = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_curated.json";
const OUT_STATS = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_curated_stats.json";

// Section → topic group from our roadmap; null = SKIP (out of MUST scope)
const SECTION_MAP = {
  "§1 Числа и выражения":                              null,                              // мостик/множества/числа — SKIP
  "§2 Преобразование выражений":                       "1. Раскрытие скобок и подобные",
  "§3 Уравнения с одной переменной":                   "2. Линейные уравнения",
  "§4 Функции и их графики":                           null,                              // NICE only — отложено
  "§5 Линейная функция":                               "6. Линейная функция",
  "§6 Степень и её свойства":                          "3. Степени",
  "§7 Одночлены":                                      "3. Степени и одночлены",
  "§8 Сумма и разность многочленов":                   "4. Многочлены",
  "§9 Произведение одночлена и многочлена":            "4. Многочлены (вынесение)",
  "§10 Произведение многочленов":                      "4. Многочлены (умножение, группировка)",
  "§11 Квадрат суммы и квадрат разности":              "5. ФСУ — квадраты",
  "§12 Разность квадратов, сумма и разность кубов":    "5. ФСУ — разность квадратов и кубы",
  "§13 Преобразование целых выражений":                "5. ФСУ — применение",
  "§14 Линейные уравнения с двумя переменными":        "7. Системы уравнений",
  "§15 Решение систем линейных уравнений":             "7. Системы уравнений"
};

// Map topic group → roadmap chapter (for ordering & stats)
const GROUP_ORDER = [
  "1. Раскрытие скобок и подобные",
  "2. Линейные уравнения",
  "3. Степени",
  "3. Степени и одночлены",
  "4. Многочлены",
  "4. Многочлены (вынесение)",
  "4. Многочлены (умножение, группировка)",
  "5. ФСУ — квадраты",
  "5. ФСУ — разность квадратов и кубы",
  "5. ФСУ — применение",
  "6. Линейная функция",
  "7. Системы уравнений"
];

const idx = JSON.parse(fs.readFileSync(SRC, "utf8"));
const all = idx.problems;

const curated = [];
const skipped = [];
const figureCount = {};
const stats = {};

for (const p of all) {
  const group = p.section ? SECTION_MAP[p.section] : null;
  if (!group) {
    skipped.push({ number: p.number, page: p.page, section: p.section, reason: !p.section ? "no section" : "SKIP" });
    continue;
  }
  // Drop "Пример N" boilerplate noise (very low numbers like 1-2 with тривиальное условие)
  // Actually keep them — they may still be useful, just may need cleaning later
  curated.push({ ...p, topic_group: group });
  stats[group] = (stats[group] || 0) + 1;
  if (p.has_figure) figureCount[group] = (figureCount[group] || 0) + 1;
}

const stats_sorted = {};
for (const g of GROUP_ORDER) {
  stats_sorted[g] = {
    total: stats[g] || 0,
    with_figures: figureCount[g] || 0,
    without_figures: (stats[g] || 0) - (figureCount[g] || 0)
  };
}

const out = {
  source: idx.source,
  curated_for: "Репетитор · 7 класс · MUST-блоки роадмапа",
  problems_total: curated.length,
  problems_skipped: skipped.length,
  problems_with_figures: curated.filter(p => p.has_figure).length,
  topic_groups: stats_sorted,
  problems: curated
};

const outStats = {
  curated_total: curated.length,
  skipped_total: skipped.length,
  skipped_breakdown: skipped.reduce((acc, s) => {
    const k = s.section || "no section";
    acc[k] = (acc[k] || 0) + 1;
    return acc;
  }, {}),
  topic_groups: stats_sorted
};

fs.writeFileSync(OUT_CURATED, JSON.stringify(out, null, 2));
fs.writeFileSync(OUT_STATS, JSON.stringify(outStats, null, 2));

console.log(`=== CURATION ===`);
console.log(`source: ${all.length} problems`);
console.log(`curated (MUST only): ${curated.length}`);
console.log(`skipped: ${skipped.length}`);
console.log(`\nskipped breakdown:`);
Object.entries(outStats.skipped_breakdown).forEach(([k, v]) => console.log(`  ${k}: ${v}`));
console.log(`\nby topic group (curated):`);
for (const g of GROUP_ORDER) {
  const s = stats_sorted[g];
  if (!s.total) continue;
  console.log(`  ${g}: ${s.total} (${s.without_figures} pure text, ${s.with_figures} с рис.)`);
}
console.log(`\nfiles:\n  ${OUT_CURATED}\n  ${OUT_STATS}`);
