#!/usr/bin/env node
// Aggregate non-verbatim Zvavich reference pages into one index.
import fs from "node:fs";
import path from "node:path";

const DIR = process.env.ZVAVICH_REF_OUT_DIR
  || "/home/dev/palomatika/storage/app/tasks/alg/grade_7/_ref/zvavich_reference_pages";
const OUT = process.env.ZVAVICH_REF_INDEX
  || "/home/dev/palomatika/storage/app/tasks/alg/grade_7/_ref/zvavich_reference_index.json";
const STATS = process.env.ZVAVICH_REF_STATS
  || "/home/dev/palomatika/storage/app/tasks/alg/grade_7/_ref/zvavich_reference_stats.json";

if (!fs.existsSync(DIR)) {
  console.error(`missing dir: ${DIR}`);
  process.exit(1);
}

const files = fs.readdirSync(DIR).filter((file) => /^p\d+\.json$/.test(file)).sort();
const problems = [];
const bySkill = {};
const byDifficulty = {};
const pagesWithFigures = new Set();

for (const file of files) {
  const pageRef = JSON.parse(fs.readFileSync(path.join(DIR, file), "utf8"));
  for (const problem of pageRef.problems ?? []) {
    const enriched = {
      ...problem,
      page: pageRef.page,
      source: pageRef.source,
      copyright_mode: pageRef.copyright_mode,
    };
    problems.push(enriched);

    for (const skillId of problem.skill_ids ?? []) {
      bySkill[skillId] = (bySkill[skillId] ?? 0) + 1;
    }
    byDifficulty[problem.difficulty] = (byDifficulty[problem.difficulty] ?? 0) + 1;
    if (problem.has_figure) pagesWithFigures.add(pageRef.page);
  }
}

const index = {
  source: "Звавич Л.И. и др., дидактические материалы, алгебра 7",
  mode: "non_verbatim_reference",
  pages_processed: files.length,
  problems_total: problems.length,
  problems,
};

const stats = {
  pages_processed: files.length,
  problems_total: problems.length,
  pages_with_figures: pagesWithFigures.size,
  by_difficulty: byDifficulty,
  by_skill: Object.fromEntries(Object.entries(bySkill).sort((a, b) => b[1] - a[1])),
};

fs.writeFileSync(OUT, JSON.stringify(index, null, 2));
fs.writeFileSync(STATS, JSON.stringify(stats, null, 2));

console.log(`pages: ${files.length}`);
console.log(`refs: ${problems.length}`);
console.log(`index: ${OUT}`);
console.log(`stats: ${STATS}`);
