import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp } from "../helpers/test-app.js";

test("protected routes redirect to login and valid login creates a session", async () => {
  const { app, cleanup } = createTestApp();
  const agent = request.agent(app);

  const redirectResponse = await agent.get("/");
  assert.equal(redirectResponse.status, 302);
  assert.equal(redirectResponse.headers.location, "/login");

  const invalidResponse = await agent.post("/api/login").send({ password: "0000" });
  assert.equal(invalidResponse.status, 401);

  const loginResponse = await agent.post("/api/login").send({ password: "2427" });
  assert.equal(loginResponse.status, 200);
  assert.equal(loginResponse.body.ok, true);

  const boardResponse = await agent.get("/");
  assert.equal(boardResponse.status, 200);
  assert.match(boardResponse.text, /Agent Board/);

  cleanup();
});
