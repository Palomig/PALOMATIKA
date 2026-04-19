import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { createServer } from "node:http";
import { createApp } from "../../src/app.js";
import { loadConfig } from "../../src/config.js";

function writeFixtureSkills(baseDir) {
  const codexSkills = path.join(baseDir, "codex-home", "skills", "brainstorming");
  const pluginSkill = path.join(
    baseDir,
    "codex-home",
    "plugins",
    "cache",
    "openai-curated",
    "github",
    "x123",
    "skills",
    "gh-fix-ci"
  );

  fs.mkdirSync(codexSkills, { recursive: true });
  fs.mkdirSync(pluginSkill, { recursive: true });

  fs.writeFileSync(path.join(codexSkills, "SKILL.md"), 'description: "Test brainstorming skill"\n', "utf8");
  fs.writeFileSync(path.join(pluginSkill, "SKILL.md"), 'description: "Fix CI issues"\n', "utf8");
}

function writeFixtureMcp(baseDir) {
  const repoRoot = path.join(baseDir, "repo");
  fs.mkdirSync(repoRoot, { recursive: true });
  fs.writeFileSync(
    path.join(repoRoot, ".mcp.json"),
    JSON.stringify(
      {
        mcpServers: {
          "palomatika-db": {
            command: "node",
            args: ["/tmp/palomatika-db/index.js"]
          },
          smartcart: {
            command: "node",
            args: ["/tmp/smartcart/index.js"]
          }
        }
      },
      null,
      2
    ),
    "utf8"
  );
}

export function createTestConfig() {
  const baseDir = fs.mkdtempSync(path.join(os.tmpdir(), "agent-board-"));
  writeFixtureSkills(baseDir);
  writeFixtureMcp(baseDir);

  return loadConfig({
    PORT: "0",
    APP_PASSWORD: "2427",
    INTEGRATION_SECRET: "test-secret",
    DB_PATH: path.join(baseDir, "data", "agent-board.sqlite"),
    REPO_ROOT: path.join(baseDir, "repo"),
    CODEX_HOME: path.join(baseDir, "codex-home"),
    NODE_ENV: "test"
  });
}

export function createTestApp() {
  const config = createTestConfig();
  const app = createApp({ config });

  function cleanup() {
    app.locals.agentBoard.events.close();
    app.locals.agentBoard.db.close();
  }

  return { app, config, cleanup };
}

export async function withTestServer(app, callback) {
  const server = createServer(app);

  await new Promise((resolve) => {
    server.listen(0, "127.0.0.1", resolve);
  });

  const address = server.address();
  const baseUrl = `http://127.0.0.1:${address.port}`;

  try {
    return await callback(baseUrl);
  } finally {
    await new Promise((resolve, reject) => {
      server.close((error) => {
        if (error) {
          reject(error);
          return;
        }

        resolve();
      });
    });
  }
}
