#!/usr/bin/env node

import assert from "node:assert/strict";
import fs from "node:fs";

const path = "storage/app/tasks/alg/grade_7/skills.json";
const pageBuilderPath = "scripts/build-grade7-skill-pages.mjs";
assert.ok(fs.existsSync(path), "skills.json must exist; run scripts/generate-grade7-skill-bank.mjs first");
assert.ok(fs.existsSync(pageBuilderPath), "static page builder must exist");

const data = JSON.parse(fs.readFileSync(path, "utf8"));
const pageBuilder = fs.readFileSync(pageBuilderPath, "utf8");

const difficultyScore = (expression) => {
  const text = String(expression ?? "");
  const operators = text.match(/\\cdot|[()+\-:=^{}]/g)?.length ?? 0;
  return operators + text.length / 18;
};

const referenceComplexityScore = (task) => {
  const text = `${task.expression ?? ""} ${task.prompt ?? ""}`.toLowerCase();
  let score = 0;
  if ((text.match(/\(/g)?.length ?? 0) >= 2) score += 1;
  if ((text.match(/=/g)?.length ?? 0) >= 1 && text.includes("x")) score += 1;
  if (/[xyzmnab]/.test(text) && text.match(/[xyzmnab]/g)?.length >= 2) score += 1;
  if (text.includes("^2") || text.includes("^3")) score += 1;
  if (text.includes("{")) score += 1;
  if (text.includes(":") || text.includes("\\cdot")) score += 1;
  if (/(пропуск|ошибк|проверьте|разложите|докажите|сравните|найдите)/.test(text)) score += 1;
  if (text.length >= 34) score += 1;
  return score;
};

assert.equal(data.grade, 7);
assert.equal(data.subject, "algebra");
assert.ok(data.skills.length >= 25, "skill bank should be split into narrow skill pages");
assert.ok(pageBuilder.includes(".taskgrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}"), "task grid should show three tasks per row on desktop");
assert.ok(!pageBuilder.includes(".level-high .taskgrid{grid-template-columns:repeat(2,minmax(0,1fr))}"), "high level should not use a separate two-column grid");
assert.ok(!pageBuilder.includes(".expr .katex{font-size:1.08em;white-space:nowrap}"), "KaTeX expressions should not force long tasks into a single unbreakable line");
assert.ok(pageBuilder.includes(".expr .katex .base{display:inline;white-space:normal}"), "KaTeX inner boxes should allow long equations to wrap inside task cards");

for (const skill of data.skills) {
  assert.ok(skill.id, "skill has id");
  assert.ok(skill.slug, `${skill.id} has slug`);
  assert.ok(skill.title, `${skill.id} has title`);
  assert.ok(skill.task_type, `${skill.id} has task_type`);
  assert.equal(skill.reference_profile?.mode, "difficulty_calibrated_from_non_verbatim_references", `${skill.slug} should keep its reference calibration profile`);
  assert.ok(skill.reference_profile.zvavich_refs + skill.reference_profile.makarychev_refs > 0, `${skill.slug} should be backed by Zvavich or Makarychev references`);
  assert.equal(skill.levels.length, 3, `${skill.slug} must have three levels`);
  assert.deepEqual(skill.levels.map(level => level.id), ["simple", "medium", "high"]);
  assert.equal(skill.homework_sets.length, 3, `${skill.slug} must have three homework sets`);

  const averageDifficulty = Object.fromEntries(skill.levels.map(level => [
    level.id,
    level.tasks.reduce((sum, task) => sum + difficultyScore(task.expression), 0) / level.tasks.length,
  ]));
  assert.ok(averageDifficulty.medium > averageDifficulty.simple, `${skill.slug} medium level should be structurally harder than simple`);
  assert.ok(averageDifficulty.high > averageDifficulty.medium, `${skill.slug} high level should be structurally harder than medium`);

  const averageReferenceComplexity = Object.fromEntries(skill.levels.map(level => [
    level.id,
    level.tasks.reduce((sum, task) => sum + referenceComplexityScore(task), 0) / level.tasks.length,
  ]));
  assert.ok(averageReferenceComplexity.high >= averageReferenceComplexity.medium - 0.25, `${skill.slug} high level should preserve Zvavich/Makarychev-style complexity markers`);

  if (["system-check-solution", "system-solve"].includes(skill.slug)) {
    const highExpressions = skill.levels.find(level => level.id === "high").tasks.map(task => task.expression).join("\n");
    assert.match(highExpressions, /=\s*-?\d*y\b/, `${skill.slug} high level should include systems with a variable on the right side of equals`);
  }

  for (const level of skill.levels) {
    assert.ok(level.tasks.length >= 15, `${skill.slug}/${level.id} should have enough homework tasks`);

    for (const task of level.tasks) {
      assert.equal(task.task_type, skill.task_type, `${skill.slug} mixes task types`);
      assert.equal(task.skill, skill.id, `${skill.slug} task points to another skill`);
      assert.ok(task.answer !== null && task.answer !== undefined && task.answer !== "", `${skill.slug} task ${task.id} has answer`);
      if (["system-check-solution", "system-solve"].includes(skill.slug)) {
        assert.ok(task.expression.includes("\\begin{cases}"), `${skill.slug} task ${task.id} should render as a KaTeX cases system`);
        assert.ok(!task.expression.trim().startsWith("{ "), `${skill.slug} task ${task.id} should not use raw brace notation for systems`);
      }

      for (const field of ["expression", "prompt"]) {
        const value = task[field] ?? "";
        assert.ok(!value.includes("*"), `${skill.slug} task ${task.id} ${field} contains raw *`);
        assert.ok(!value.includes("/"), `${skill.slug} task ${task.id} ${field} contains raw /`);
      }
    }
  }

  for (const homeworkSet of skill.homework_sets) {
    assert.ok(homeworkSet.tasks.length >= 15 && homeworkSet.tasks.length <= 20, `${skill.slug}/${homeworkSet.id} should contain 15-20 tasks`);
    assert.equal(homeworkSet.tasks.length, homeworkSet.tasks_count, `${skill.slug}/${homeworkSet.id} tasks_count mismatch`);

    for (const task of homeworkSet.tasks) {
      assert.equal(task.task_type, skill.task_type, `${skill.slug}/${homeworkSet.id} mixes task types`);
      assert.equal(task.skill, skill.id, `${skill.slug}/${homeworkSet.id} task points to another skill`);
      if (["system-check-solution", "system-solve"].includes(skill.slug)) {
        assert.ok(task.expression.includes("\\begin{cases}"), `${skill.slug}/${homeworkSet.id} task ${task.id} should render as a KaTeX cases system`);
        assert.ok(!task.expression.trim().startsWith("{ "), `${skill.slug}/${homeworkSet.id} task ${task.id} should not use raw brace notation for systems`);
      }

      for (const field of ["expression", "prompt"]) {
        const value = task[field] ?? "";
        assert.ok(!value.includes("*"), `${skill.slug}/${homeworkSet.id} task ${task.id} ${field} contains raw *`);
        assert.ok(!value.includes("/"), `${skill.slug}/${homeworkSet.id} task ${task.id} ${field} contains raw /`);
      }
    }
  }
}

console.log(`ok ${data.skills.length} skills`);
