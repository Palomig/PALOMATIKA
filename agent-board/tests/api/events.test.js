import test from "node:test";
import assert from "node:assert/strict";
import request from "supertest";
import { createTestApp, withTestServer } from "../helpers/test-app.js";

test("sse endpoint streams connection and task events", async () => {
  const { app, cleanup, config } = createTestApp();
  const loginAgent = request.agent(app);
  const loginResponse = await loginAgent.post("/api/login").send({ password: "2427" });
  const cookie = loginResponse.headers["set-cookie"][0].split(";")[0];

  await withTestServer(app, async (baseUrl) => {
    const sseResponse = await fetch(`${baseUrl}/api/events`, {
      headers: {
        Cookie: cookie
      }
    });

    assert.equal(sseResponse.status, 200);
    const reader = sseResponse.body.getReader();

    const firstChunk = await reader.read();
    const firstText = new TextDecoder().decode(firstChunk.value);
    assert.match(firstText, /event: connected/);

    await fetch(`${baseUrl}/api/tasks`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Agent-Board-Secret": config.integrationSecret
      },
      body: JSON.stringify({
        title: "SSE task",
        description: "created through integration",
        project: "palomatika"
      })
    });

    const nextChunk = await reader.read();
    const nextText = new TextDecoder().decode(nextChunk.value);
    assert.match(nextText, /event: task_created/);

    reader.cancel();
  });

  cleanup();
});
