import test from "node:test";
import assert from "node:assert/strict";
import { createTestApp } from "../helpers/test-app.js";

test("database bootstrap creates tables and seeds registry", () => {
  const { app, cleanup } = createTestApp();
  const db = app.locals.agentBoard.db;

  const tables = db
    .prepare("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name ASC")
    .all()
    .map((row) => row.name);

  assert.ok(tables.includes("tasks"));
  assert.ok(tables.includes("completed_tasks"));
  assert.ok(tables.includes("agents"));
  assert.ok(tables.includes("mcp_servers"));
  assert.ok(tables.includes("skills"));

  const agentCount = db.prepare("SELECT COUNT(*) AS count FROM agents").get().count;
  const mcpCount = db.prepare("SELECT COUNT(*) AS count FROM mcp_servers").get().count;
  const skillCount = db.prepare("SELECT COUNT(*) AS count FROM skills").get().count;

  assert.ok(agentCount >= 3);
  assert.ok(mcpCount >= 2);
  assert.ok(skillCount >= 2);

  cleanup();
});
