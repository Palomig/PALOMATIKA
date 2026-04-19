import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";
import { createBoardController, groupTasksByColumn } from "../../public/board.js";

class FakeEventSource {
  constructor() {
    this.handlers = new Map();
    queueMicrotask(() => {
      if (typeof this.onopen === "function") {
        this.onopen();
      }
    });
  }

  addEventListener(name, callback) {
    this.handlers.set(name, callback);
  }
}

test("groupTasksByColumn groups tasks into board lanes", () => {
  const grouped = groupTasksByColumn([
    { id: 1, column_key: "unassigned" },
    { id: 2, column_key: "claude" },
    { id: 3, column_key: "codex" }
  ]);

  assert.equal(grouped.unassigned.length, 1);
  assert.equal(grouped.claude.length, 1);
  assert.equal(grouped.codex.length, 1);
});

test("board controller loads tasks, registry, and updates DOM", async () => {
  const dom = new JSDOM(`<!doctype html><html><body>
    <div id="board-page">
      <span id="live-indicator"></span>
      <form id="task-form"></form>
      <p id="task-form-error" hidden></p>
      <button id="logout-button" type="button"></button>
      <div id="agents-list"></div>
      <div id="mcp-list"></div>
      <div id="skills-list"></div>
      <span id="count-unassigned"></span>
      <span id="count-claude"></span>
      <span id="count-codex"></span>
      <div id="column-unassigned" class="card-lane"></div>
      <div id="column-claude" class="card-lane"></div>
      <div id="column-codex" class="card-lane"></div>
    </div>
  </body></html>`, { url: "http://localhost/" });

  const calls = [];
  const fetchImpl = async (url) => {
    calls.push(url);

    if (url === "/api/tasks") {
      return new Response(JSON.stringify({
        tasks: [{ id: 1, title: "Task", description: "Desc", project: "palomatika", column_key: "unassigned", created_at: new Date().toISOString() }]
      }), { status: 200, headers: { "Content-Type": "application/json" } });
    }

    if (url === "/api/registry") {
      return new Response(JSON.stringify({
        agents: [{ id: "agent-claude", name: "Claude", kind: "model" }],
        mcpServers: [{ id: "mcp-palomatika-db", name: "palomatika-db", description: "desc" }],
        skills: [{ id: "skill-brainstorming", name: "brainstorming", description: "desc" }]
      }), { status: 200, headers: { "Content-Type": "application/json" } });
    }

    return new Response(JSON.stringify({ ok: true }), { status: 200, headers: { "Content-Type": "application/json" } });
  };

  const controller = createBoardController({
    document: dom.window.document,
    fetchImpl,
    EventSourceImpl: FakeEventSource,
    navigatorImpl: { clipboard: { writeText: async () => {} } }
  });

  await controller.init();

  assert.deepEqual(calls.slice(0, 2).sort(), ["/api/registry", "/api/tasks"]);
  assert.match(dom.window.document.getElementById("column-unassigned").innerHTML, /Task/);
  assert.match(dom.window.document.getElementById("agents-list").innerHTML, /agent-claude/);
});
