#!/usr/bin/env node

import assert from "node:assert/strict";
import fs from "node:fs";

const path = "storage/app/tasks/alg/grade_7/skills.json";
assert.ok(fs.existsSync(path), "skills.json must exist; run scripts/generate-grade7-skill-bank.mjs first");

const data = JSON.parse(fs.readFileSync(path, "utf8"));

assert.equal(data.grade, 7);
assert.equal(data.subject, "algebra");
assert.ok(data.skills.length >= 25, "skill bank should be split into narrow skill pages");

for (const skill of data.skills) {
  assert.ok(skill.id, "skill has id");
  assert.ok(skill.slug, `${skill.id} has slug`);
  assert.ok(skill.title, `${skill.id} has title`);
  assert.ok(skill.task_type, `${skill.id} has task_type`);
  assert.equal(skill.levels.length, 3, `${skill.slug} must have three levels`);
  assert.deepEqual(skill.levels.map(level => level.id), ["simple", "medium", "high"]);

  for (const level of skill.levels) {
    assert.ok(level.tasks.length >= 15, `${skill.slug}/${level.id} should have enough homework tasks`);

    for (const task of level.tasks) {
      assert.equal(task.task_type, skill.task_type, `${skill.slug} mixes task types`);
      assert.equal(task.skill, skill.id, `${skill.slug} task points to another skill`);
      assert.ok(task.answer !== null && task.answer !== undefined && task.answer !== "", `${skill.slug} task ${task.id} has answer`);

      for (const field of ["expression", "prompt"]) {
        const value = task[field] ?? "";
        assert.ok(!value.includes("*"), `${skill.slug} task ${task.id} ${field} contains raw *`);
        assert.ok(!value.includes("/"), `${skill.slug} task ${task.id} ${field} contains raw /`);
      }
    }
  }
}

console.log(`ok ${data.skills.length} skills`);
