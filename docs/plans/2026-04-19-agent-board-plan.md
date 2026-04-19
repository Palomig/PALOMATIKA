# Agent Board Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a standalone local task board with three drag-and-drop agent columns, a completed-work history page, registry sidebars for agents/MCP servers/skills, and HTTP APIs for Jarvis and agent completion updates.

**Architecture:** Create a small `Node.js + Express` app backed by `SQLite`, serve a lightweight multi-page frontend, and use `Server-Sent Events` for real-time board/history updates. Keep the registry seeded from code, protect the UI with a single password gate, and expose simple authenticated JSON endpoints for task creation, movement, and completion publishing.

**Tech Stack:** Node.js, Express, SQLite, server-rendered/static HTML, vanilla browser JavaScript, SSE, CSS.

---

### Task 1: Scaffold the standalone app

**Files:**
- Create: `agent-board/package.json`
- Create: `agent-board/.env.example`
- Create: `agent-board/src/server.js`
- Create: `agent-board/src/config.js`
- Create: `agent-board/src/app.js`
- Create: `agent-board/README.md`

**Step 1: Write the failing bootstrap check**

Add a temporary smoke command in `package.json` that expects `src/server.js` to exist and fails before files are created.

**Step 2: Run bootstrap command to verify it fails**

Run: `cd agent-board && npm run smoke`
Expected: FAIL because the app files do not exist yet.

**Step 3: Write minimal app scaffold**

Create the package manifest, env example, and Express bootstrap files with a health route and start script.

**Step 4: Run bootstrap command to verify it passes**

Run: `cd agent-board && npm run smoke`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/package.json agent-board/.env.example agent-board/src/server.js agent-board/src/config.js agent-board/src/app.js agent-board/README.md
git commit -m "feat: scaffold agent board app"
```

### Task 2: Add SQLite schema and database bootstrap

**Files:**
- Create: `agent-board/src/db.js`
- Create: `agent-board/src/schema.sql`
- Create: `agent-board/src/seeds/registry.js`
- Create: `agent-board/tests/db/schema.test.js`

**Step 1: Write the failing test**

Add a test that initializes a temporary SQLite database and asserts the tables `tasks`, `completed_tasks`, `agents`, `mcp_servers`, and `skills` exist after bootstrap.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- schema.test.js`
Expected: FAIL because database bootstrap is not implemented.

**Step 3: Write minimal implementation**

Implement schema creation and seed insertion for registry data with stable IDs.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- schema.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/db.js agent-board/src/schema.sql agent-board/src/seeds/registry.js agent-board/tests/db/schema.test.js
git commit -m "feat: add agent board sqlite bootstrap"
```

### Task 3: Add password authentication and protected routes

**Files:**
- Modify: `agent-board/src/app.js`
- Create: `agent-board/src/auth.js`
- Create: `agent-board/src/routes/auth.js`
- Create: `agent-board/tests/auth/login.test.js`

**Step 1: Write the failing test**

Add a test that verifies unauthenticated requests to `/` redirect to `/login` and valid password login creates a session/cookie.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- login.test.js`
Expected: FAIL because auth middleware is not implemented.

**Step 3: Write minimal implementation**

Implement password-based login, logout, cookie/session protection, and route guard middleware.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- login.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/app.js agent-board/src/auth.js agent-board/src/routes/auth.js agent-board/tests/auth/login.test.js
git commit -m "feat: protect agent board with password auth"
```

### Task 4: Add task board API

**Files:**
- Create: `agent-board/src/routes/tasks.js`
- Create: `agent-board/src/repositories/tasks.js`
- Create: `agent-board/src/validators/tasks.js`
- Create: `agent-board/tests/api/tasks.test.js`

**Step 1: Write the failing test**

Add API tests for:
- `POST /api/tasks` creates `unassigned` task
- `GET /api/tasks` returns grouped tasks
- invalid `project` is rejected

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- tasks.test.js`
Expected: FAIL because task API does not exist.

**Step 3: Write minimal implementation**

Implement task creation and listing with validation for `project` and default `column_key`.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- tasks.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/routes/tasks.js agent-board/src/repositories/tasks.js agent-board/src/validators/tasks.js agent-board/tests/api/tasks.test.js
git commit -m "feat: add task board api"
```

### Task 5: Add move and completion APIs

**Files:**
- Modify: `agent-board/src/routes/tasks.js`
- Create: `agent-board/src/routes/completions.js`
- Modify: `agent-board/src/repositories/tasks.js`
- Create: `agent-board/src/repositories/completed.js`
- Create: `agent-board/tests/api/completions.test.js`

**Step 1: Write the failing test**

Add tests for:
- moving a task between columns
- manually completing a task
- posting completion by task ID with `summary`, `used_mcp_ids`, and `used_skill_ids`
- duplicate completion rejected

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- completions.test.js`
Expected: FAIL because move/completion flows are not implemented.

**Step 3: Write minimal implementation**

Implement move endpoint, archive-on-complete flow, validation of registry IDs, and completed-task persistence.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- completions.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/routes/tasks.js agent-board/src/routes/completions.js agent-board/src/repositories/tasks.js agent-board/src/repositories/completed.js agent-board/tests/api/completions.test.js
git commit -m "feat: add task movement and completion api"
```

### Task 6: Add registry API

**Files:**
- Create: `agent-board/src/routes/registry.js`
- Create: `agent-board/src/repositories/registry.js`
- Create: `agent-board/tests/api/registry.test.js`

**Step 1: Write the failing test**

Add a test that asserts `GET /api/registry` returns agents, MCP servers, and skills with stable IDs.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- registry.test.js`
Expected: FAIL because registry endpoint does not exist.

**Step 3: Write minimal implementation**

Implement registry read API using the seeded tables.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- registry.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/routes/registry.js agent-board/src/repositories/registry.js agent-board/tests/api/registry.test.js
git commit -m "feat: expose registry api"
```

### Task 7: Add SSE event broadcaster

**Files:**
- Create: `agent-board/src/events.js`
- Create: `agent-board/src/routes/events.js`
- Modify: `agent-board/src/routes/tasks.js`
- Modify: `agent-board/src/routes/completions.js`
- Create: `agent-board/tests/api/events.test.js`

**Step 1: Write the failing test**

Add tests that subscribe to `/api/events` and verify the server emits `task_created`, `task_moved`, and `task_completed` events after corresponding API calls.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- events.test.js`
Expected: FAIL because SSE broadcasting is not implemented.

**Step 3: Write minimal implementation**

Implement SSE client registration, heartbeat/reconnect-safe headers, and event emission hooks from task mutations.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- events.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/events.js agent-board/src/routes/events.js agent-board/src/routes/tasks.js agent-board/src/routes/completions.js agent-board/tests/api/events.test.js
git commit -m "feat: stream board events over sse"
```

### Task 8: Build the login and board UI

**Files:**
- Create: `agent-board/src/routes/pages.js`
- Create: `agent-board/public/styles.css`
- Create: `agent-board/public/board.js`
- Create: `agent-board/views/login.html`
- Create: `agent-board/views/board.html`

**Step 1: Write the failing test**

Add a browser-facing smoke test or route test that verifies `/login` and `/` render expected headings and that the board page includes the three columns and registry sections.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- board-page.test.js`
Expected: FAIL because the pages are not implemented.

**Step 3: Write minimal implementation**

Implement login page, board page layout, task rendering, registry sidebar, and live connection indicator.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- board-page.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/routes/pages.js agent-board/public/styles.css agent-board/public/board.js agent-board/views/login.html agent-board/views/board.html
git commit -m "feat: add board ui"
```

### Task 9: Add drag-and-drop behavior and manual completion controls

**Files:**
- Modify: `agent-board/public/board.js`
- Modify: `agent-board/views/board.html`
- Create: `agent-board/tests/ui/board-interactions.test.js`

**Step 1: Write the failing test**

Add a UI test that drags a task between columns and clicks `Завершить`, asserting the correct API calls are made and the DOM updates.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- board-interactions.test.js`
Expected: FAIL because drag-and-drop and completion controls are incomplete.

**Step 3: Write minimal implementation**

Implement HTML5 drag-and-drop handlers, optimistic updates or refresh-on-success, and manual completion action wiring.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- board-interactions.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/public/board.js agent-board/views/board.html agent-board/tests/ui/board-interactions.test.js
git commit -m "feat: add board interactions"
```

### Task 10: Build the completed history page

**Files:**
- Create: `agent-board/public/completed.js`
- Create: `agent-board/views/completed.html`
- Modify: `agent-board/src/routes/pages.js`
- Create: `agent-board/tests/ui/completed-page.test.js`

**Step 1: Write the failing test**

Add a test that verifies the completed page renders wide horizontal entries with project, agent, summary, MCP IDs, and skill IDs.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- completed-page.test.js`
Expected: FAIL because the page is not implemented.

**Step 3: Write minimal implementation**

Implement completed history rendering, filtering/search hooks if kept, and SSE refresh wiring for newly completed work.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- completed-page.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/public/completed.js agent-board/views/completed.html agent-board/src/routes/pages.js agent-board/tests/ui/completed-page.test.js
git commit -m "feat: add completed history page"
```

### Task 11: Add integration auth and Jarvis-friendly API documentation

**Files:**
- Modify: `agent-board/src/routes/tasks.js`
- Modify: `agent-board/src/routes/completions.js`
- Modify: `agent-board/src/config.js`
- Modify: `agent-board/README.md`
- Create: `agent-board/tests/api/integration-auth.test.js`

**Step 1: Write the failing test**

Add tests that verify integration endpoints reject missing or invalid shared secrets and accept valid requests.

**Step 2: Run test to verify it fails**

Run: `cd agent-board && npm test -- integration-auth.test.js`
Expected: FAIL because integration auth is not implemented.

**Step 3: Write minimal implementation**

Protect machine-to-machine endpoints with a shared secret header and document exact request examples for Jarvis and agent completions.

**Step 4: Run test to verify it passes**

Run: `cd agent-board && npm test -- integration-auth.test.js`
Expected: PASS

**Step 5: Commit**

```bash
git add agent-board/src/routes/tasks.js agent-board/src/routes/completions.js agent-board/src/config.js agent-board/README.md agent-board/tests/api/integration-auth.test.js
git commit -m "feat: secure agent board integrations"
```

### Task 12: Verify end-to-end local run

**Files:**
- Modify: `agent-board/README.md`
- Modify: `docs/plans/2026-04-19-agent-board-design.md`
- Modify: `docs/plans/2026-04-19-agent-board-plan.md`

**Step 1: Run the automated test suite**

Run: `cd agent-board && npm test`
Expected: PASS

**Step 2: Run the app locally**

Run: `cd agent-board && npm install && npm run dev`
Expected: server starts, login works, board loads, SSE connects.

**Step 3: Perform manual verification**

Verify:
- login with shared password
- create task through API
- drag task from `Без агента` to `Claude` and `Codex`
- complete a task from UI
- complete a task through API with MCP and skill IDs
- confirm completed history updates live

**Step 4: Update docs with any final run notes**

Record any required environment variables, default port, and integration header names.

**Step 5: Commit**

```bash
git add agent-board/README.md docs/plans/2026-04-19-agent-board-design.md docs/plans/2026-04-19-agent-board-plan.md
git commit -m "docs: finalize agent board delivery notes"
```
