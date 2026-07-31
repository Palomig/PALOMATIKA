#!/usr/bin/env node
// Add `level` field to each task in non-FSU grade-7 topic JSONs based on
// scripts/difficulty-classification.json. Preserves block structure.

import fs from "node:fs";
import path from "node:path";

const TOPIC_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const VERDICT = "/home/dev/palomatika/scripts/difficulty-classification.json";
const TOPICS = ["01", "02", "03", "04", "05", "06", "10", "11"];
const stamp = new Date().toISOString().replace(/[:T.]/g, "-").slice(0, 15);

const verdict = JSON.parse(fs.readFileSync(VERDICT, "utf8"));
const labelById = new Map(verdict.items.map(v => [v.id, v.final]));

const summary = [];
for (const tid of TOPICS) {
  const file = path.join(TOPIC_DIR, `topic_${tid}.json`);
  const topic = JSON.parse(fs.readFileSync(file, "utf8"));
  const counts = { base: 0, advanced: 0, extra: 0, untouched: 0 };
  topic.blocks.forEach((block, bi) => {
    block.zadaniya.forEach((zad, zi) => {
      zad.tasks.forEach((task, ti) => {
        const id = `t${tid}_b${bi}_z${zi}_t${ti}`;
        const lbl = labelById.get(id);
        if (lbl) { task.level = lbl; counts[lbl]++; }
        else { counts.untouched++; }
      });
    });
  });
  fs.writeFileSync(`${file}.bak.${stamp}`, JSON.stringify(JSON.parse(fs.readFileSync(file, "utf8")), null, 2));
  fs.writeFileSync(file, JSON.stringify(topic, null, 2));
  summary.push({ topic: tid, title: topic.meta?.title, ...counts });
  console.log(`[${tid}] ${topic.meta?.title}: base=${counts.base}, advanced=${counts.advanced}, extra=${counts.extra}` + (counts.untouched ? `, untouched=${counts.untouched}` : ""));
}
console.log("\nDone. Now run: node scripts/build-topics-overview.mjs");
