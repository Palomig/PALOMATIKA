import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("integration endpoints require the shared secret", async () => {
  const { app, cleanup, config } = createTestApp();

  const missingSecretResponse = await request(app).post("/api/tasks").send({
    title: "No secret",
    description: "",
    project: "palomatika"
  });
  assert.equal(missingSecretResponse.status, 401);

  const createResponse = await request(app)
    .post("/api/tasks")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      title: "With secret",
      description: "ok",
      project: "palomatika"
    });
  assert.equal(createResponse.status, 201);

  const completionResponse = await request(app)
    .post("/api/completions")
    .send({
      task_id: createResponse.body.task.id,
      completed_by: "codex",
      summary: "No secret",
      used_mcp_ids: [],
      used_skill_ids: []
    });
  assert.equal(completionResponse.status, 401);

  cleanup();
});
