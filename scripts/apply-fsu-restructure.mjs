#!/usr/bin/env node
// Apply FSU classification verdict from scripts/fsu-classification.json to topic_07/08/09 JSONs.
// Restructures each topic into:
//   block 1 — original "Учебник Макарычев" (base tasks only)
//   block 2 — original "Сгенерированные DeepSeek" (base tasks only) [if exists]
//   block N — "Повышенный уровень (кубы)" — all advanced tasks, grouped by original zadanie
//   block N+1 — "Дополнительный уровень (степени ≥4)" — all extra tasks, used редко
//
// Originals are backed up to *.json.bak.<timestamp> before write.
//
// Usage:
//   node scripts/apply-fsu-restructure.mjs            # apply
//   node scripts/apply-fsu-restructure.mjs --dry      # preview only

import fs from "node:fs";
import path from "node:path";

const DRY = process.argv.includes("--dry");
const TOPIC_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const VERDICT = "/home/dev/palomatika/scripts/fsu-classification.json";

const verdict = JSON.parse(fs.readFileSync(VERDICT, "utf8"));
const labelById = new Map(verdict.items.map(v => [v.id, v.final]));

const stamp = new Date().toISOString().replace(/[:T.]/g, "-").slice(0, 15);

const ADVANCED_TITLE = "Повышенный уровень (кубы)";
const EXTRA_TITLE    = "Дополнительный уровень (степени ≥4) — используется редко";

const TOPICS = ["07", "08", "09"];

function clone(o) { return JSON.parse(JSON.stringify(o)); }

// Bucket tasks of a single block into base/advanced/extra by their final label.
// Preserves zadanie grouping (instruction).
function bucketize(block, blockIdx, topicId) {
  const buckets = { base: [], advanced: [], extra: [] };
  block.zadaniya.forEach((zad, zi) => {
    const taskGroups = { base: [], advanced: [], extra: [] };
    zad.tasks.forEach((task, ti) => {
      const id = `t${topicId}_b${blockIdx}_z${zi}_t${ti}`;
      const lbl = labelById.get(id) || "base";
      const bucket = (lbl === "advanced" || lbl === "extra") ? lbl : "base";
      taskGroups[bucket].push({ ...task, level: bucket });
    });
    for (const k of ["base", "advanced", "extra"]) {
      if (taskGroups[k].length) {
        buckets[k].push({ ...zad, tasks: taskGroups[k] });
      }
    }
  });
  return buckets;
}

function renumber(zadaniya) {
  return zadaniya.map((z, i) => ({ ...z, number: i + 1 }));
}

let summary = [];

for (const tid of TOPICS) {
  const file = path.join(TOPIC_DIR, `topic_${tid}.json`);
  const topic = JSON.parse(fs.readFileSync(file, "utf8"));
  const orig = clone(topic);

  const newBlocks = [];
  const advancedAcc = [];
  const extraAcc = [];

  topic.blocks.forEach((block, bi) => {
    const { base, advanced, extra } = bucketize(block, bi, tid);
    if (base.length) {
      newBlocks.push({ ...block, zadaniya: renumber(base) });
    }
    advanced.forEach(z => advancedAcc.push({ ...z, _src_block: block.title }));
    extra.forEach(z => extraAcc.push({ ...z, _src_block: block.title }));
  });

  // Renumber base blocks (preserve original numbers if they still make sense)
  newBlocks.forEach((b, i) => { b.number = i + 1; });

  if (advancedAcc.length) {
    newBlocks.push({
      number: newBlocks.length + 1,
      title: ADVANCED_TITLE,
      zadaniya: renumber(advancedAcc.map(({ _src_block, ...rest }) => rest))
    });
  }
  if (extraAcc.length) {
    newBlocks.push({
      number: newBlocks.length + 1,
      title: EXTRA_TITLE,
      zadaniya: renumber(extraAcc.map(({ _src_block, ...rest }) => rest))
    });
  }

  const totals = orig.blocks.map(b => b.zadaniya.reduce((s, z) => s + z.tasks.length, 0));
  const newTotals = newBlocks.map(b => b.zadaniya.reduce((s, z) => s + z.tasks.length, 0));
  const baseSum = newBlocks.filter(b => b.title !== ADVANCED_TITLE && b.title !== EXTRA_TITLE)
                           .reduce((s, b) => s + b.zadaniya.reduce((ss, z) => ss + z.tasks.length, 0), 0);
  const advSum = (newBlocks.find(b => b.title === ADVANCED_TITLE)?.zadaniya || []).reduce((s, z) => s + z.tasks.length, 0);
  const extSum = (newBlocks.find(b => b.title === EXTRA_TITLE)?.zadaniya || []).reduce((s, z) => s + z.tasks.length, 0);

  summary.push({ topic: tid, title: topic.meta?.title, totals_orig: totals, totals_new: newTotals, base: baseSum, advanced: advSum, extra: extSum });

  topic.blocks = newBlocks;

  if (!DRY) {
    fs.writeFileSync(`${file}.bak.${stamp}`, JSON.stringify(orig, null, 2));
    fs.writeFileSync(file, JSON.stringify(topic, null, 2));
    console.log(`[${tid}] written; backup → ${path.basename(file)}.bak.${stamp}`);
  } else {
    console.log(`[${tid}] DRY: would write ${newBlocks.length} blocks (${baseSum} base, ${advSum} advanced, ${extSum} extra)`);
  }
}

console.log("\n=== Summary ===");
console.table(summary);

if (!DRY) {
  console.log("\nNext: node scripts/build-topics-overview.mjs");
}
