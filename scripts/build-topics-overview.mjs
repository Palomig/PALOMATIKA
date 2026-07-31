#!/usr/bin/env node
// Bundle all 11 topic_XX.json into a single overview file for the viewer.
import fs from "node:fs";
import path from "node:path";

const DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";
const OUT = "/var/www/html/grade7-topics.json";

const files = fs.readdirSync(DIR).filter(f => /^topic_\d+\.json$/.test(f)).sort();
const topics = files.map(f => JSON.parse(fs.readFileSync(path.join(DIR, f), "utf8")));

const overview = {
  generated_at: new Date().toISOString(),
  source: "Макарычев Ю.Н. — Алгебра 7 класс, 2023",
  topics_count: topics.length,
  topics
};

fs.writeFileSync(OUT, JSON.stringify(overview, null, 2));
const total = topics.reduce((s, t) =>
  s + t.blocks.reduce((bs, b) => bs + b.zadaniya.reduce((ss, z) => ss + z.tasks.length, 0), 0), 0);
const generated = topics.reduce((s, t) => {
  const b2 = t.blocks.find(b => b.number === 2);
  return s + (b2 ? b2.zadaniya.reduce((ss, z) => ss + z.tasks.length, 0) : 0);
}, 0);
console.log(`bundled ${topics.length} topics, ${total} tasks (${generated} generated) → ${OUT}  (${(fs.statSync(OUT).size/1024).toFixed(0)} KB)`);
