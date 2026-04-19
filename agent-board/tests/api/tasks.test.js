import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("task API creates unassigned tasks and validates projects", async () => {
  const { app, cleanup, config } = createTestApp();
  const agent = request.agent(app);
  await agent.post("/api/login").send({ password: "2427" });

  const invalidResponse = await agent.post("/api/tasks").send({
    title: "Broken",
    description: "bad project",
    project: "unknown"
  });
  assert.equal(invalidResponse.status, 400);

  const createResponse = await request(app)
    .post("/api/tasks")
    .set("X-Agent-Board-Secret", config.integrationSecret)
    .send({
      title: "Новая задача",
      description: "Описание задачи",
      project: "palomatika"
    });

  assert.equal(createResponse.status, 201);
  assert.equal(createResponse.body.task.column_key, "unassigned");

  const listResponse = await agent.get("/api/tasks");
  assert.equal(listResponse.status, 200);
  assert.equal(listResponse.body.tasks.length, 1);
  assert.equal(listResponse.body.tasks[0].title, "Новая задача");

  cleanup();
});
