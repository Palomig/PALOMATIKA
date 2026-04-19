import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { filterCompletedEntries } from "../../public/completed.js";
import { createTestApp } from "../helpers/test-app.js";

test("completed page renders history shell", async () => {
  const { app, cleanup } = createTestApp();
  const agent = request.agent(app);
  await agent.post("/api/login").send({ password: "2427" });

  const response = await agent.get("/completed");
  assert.equal(response.status, 200);
  assert.match(response.text, /Completed Work/);
  assert.match(response.text, /History Filters/);

  cleanup();
});

test("completed filters match by project and text query", () => {
  const filtered = filterCompletedEntries(
    [
      { project: "palomatika", title: "Alpha", description: "", summary: "used mcp-palomatika-db", completed_by: "codex", task_id: 1, used_mcp_ids: ["mcp-palomatika-db"], used_skill_ids: [] },
      { project: "evrium", title: "Beta", description: "", summary: "nothing", completed_by: "claude", task_id: 2, used_mcp_ids: [], used_skill_ids: ["skill-brainstorming"] }
    ],
    { project: "palomatika", query: "mcp-palomatika-db" }
  );

  assert.equal(filtered.length, 1);
  assert.equal(filtered[0].title, "Alpha");
});
