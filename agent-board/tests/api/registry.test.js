import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("registry endpoint returns agents, mcp servers, and skills with ids", async () => {
  const { app, cleanup } = createTestApp();
  const agent = request.agent(app);
  await agent.post("/api/login").send({ password: "2427" });

  const response = await agent.get("/api/registry");
  assert.equal(response.status, 200);
  assert.ok(response.body.agents.some((item) => item.id === "agent-claude"));
  assert.ok(response.body.mcpServers.some((item) => item.id === "mcp-palomatika-db"));
  assert.ok(response.body.skills.some((item) => item.id === "skill-brainstorming"));

  cleanup();
});
