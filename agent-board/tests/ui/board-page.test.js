import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("board page renders columns and registry sections", async () => {
  const { app, cleanup } = createTestApp();
  const agent = request.agent(app);
  await agent.post("/api/login").send({ password: "2427" });

  const response = await agent.get("/");
  assert.equal(response.status, 200);
  assert.match(response.text, /1\. Без агента/);
  assert.match(response.text, /2\. Claude/);
  assert.match(response.text, /3\. Codex/);
  assert.match(response.text, /MCP Servers/);
  assert.match(response.text, /Skills/);

  cleanup();
});
