#!/usr/bin/env node
// Aggregate all per-page makarychev_pages/p*.json into a single _index.json + stats.
import fs from "node:fs";
import path from "node:path";

const DIR = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/makarychev_pages";
const OUT = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_index.json";
const STATS = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_stats.json";

const files = fs.readdirSync(DIR).filter(f => /^p\d+\.json$/.test(f)).sort();
console.log(`Found ${files.length} page files`);

const allProblems = [];
const byChapter = {};
const bySection = {};
const errorPages = [];
const figurePages = new Set();

for (const f of files) {
  const data = JSON.parse(fs.readFileSync(path.join(DIR, f), "utf8"));
  if (data.error) {
    errorPages.push({ page: data.page, error: data.error });
    continue;
  }
  for (const prob of data.problems) {
    const enriched = {
      ...prob,
      page: data.page,
      chapter: data.chapter,
      chapter_title: data.chapter_title,
      section: data.section
    };
    allProblems.push(enriched);
    if (prob.has_figure) figurePages.add(data.page);

    const chKey = `Гл.${data.chapter}`;
    byChapter[chKey] = (byChapter[chKey] || 0) + 1;
    if (data.section) bySection[data.section] = (bySection[data.section] || 0) + 1;
  }
}

const numbers = allProblems.map(p => p.number).filter(Number.isInteger);
const minN = Math.min(...numbers);
const maxN = Math.max(...numbers);

const index = {
  source: "Макарычев Ю.Н. — Алгебра 7 класс, 2023",
  pages_processed: files.length,
  problems_total: allProblems.length,
  problems_range: { min: minN, max: maxN },
  pages_with_figures: figurePages.size,
  problems
: allProblems
};

const stats = {
  pages_processed: files.length,
  problems_total: allProblems.length,
  problems_with_figures: allProblems.filter(p => p.has_figure).length,
  errors: errorPages,
  by_chapter: byChapter,
  by_section: bySection,
  number_range: { min: minN, max: maxN },
  unique_numbers: new Set(numbers).size
};

fs.writeFileSync(OUT, JSON.stringify(index, null, 2));
fs.writeFileSync(STATS, JSON.stringify(stats, null, 2));

console.log(`\n--- STATS ---`);
console.log(`pages: ${stats.pages_processed}  problems: ${stats.problems_total}  unique #: ${stats.unique_numbers}`);
console.log(`with figures: ${stats.problems_with_figures}  errors: ${errorPages.length}`);
console.log(`number range: ${minN}…${maxN}`);
console.log(`\nby chapter:`);
Object.entries(byChapter).sort().forEach(([k, v]) => console.log(`  ${k}: ${v}`));
console.log(`\nby section:`);
Object.entries(bySection).sort().forEach(([k, v]) => console.log(`  ${k}: ${v}`));
if (errorPages.length) {
  console.log(`\nerror pages: ${errorPages.map(e => e.page).join(", ")}`);
}
console.log(`\nindex: ${OUT}`);
console.log(`stats: ${STATS}`);
