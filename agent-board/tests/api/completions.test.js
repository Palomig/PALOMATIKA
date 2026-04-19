import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("manual and API completions archive tasks into completed history", async () => {
  const { app, cleanup, config } = createTestApp();
  const agent = request.agent(app);
  await agent.post("/api/login").send({ password: "2427" });

  const createFirst = await request(app)
    .post("/api/tasks")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      title: "Claude task",
      description: "Move then complete manually",
      project: "palomatika"
    });
  const firstTaskId = createFirst.body.task.id;

  const moveResponse = await agent.patch(`/api/tasks/${firstTaskId}/move`).send({ column_key: "claude" });
  assert.equal(moveResponse.status, 200);
  assert.equal(moveResponse.body.task.column_key, "claude");

  const manualComplete = await agent.post(`/api/tasks/${firstTaskId}/complete`).send({});
  assert.equal(manualComplete.status, 200);
  assert.equal(manualComplete.body.completed.completed_by, "claude");

  const createSecond = await request(app)
    .post("/api/tasks")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      title: "Codex task",
      description: "Complete via API",
      project: "evrium"
    });
  const secondTaskId = createSecond.body.task.id;

  const completedResponse = await request(app)
    .post("/api/completions")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      task_id: secondTaskId,
      completed_by: "codex",
      summary: "Shipped end-to-end work",
      used_mcp_ids: ["mcp-palomatika-db"],
      used_skill_ids: ["skill-brainstorming"]
    });

  assert.equal(completedResponse.status, 201);
  assert.equal(completedResponse.body.completed.completed_by, "codex");

  const duplicateResponse = await request(app)
    .post("/api/completions")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      task_id: secondTaskId,
      completed_by: "codex",
      summary: "Again",
      used_mcp_ids: [],
      used_skill_ids: []
    });
  assert.equal(duplicateResponse.status, 404);

  const historyResponse = await agent.get("/api/completed");
  assert.equal(historyResponse.status, 200);
  assert.equal(historyResponse.body.completed.length, 2);

  cleanup();
});
