import path from "node:path";

const PROJECT_ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), "..");

export function loadConfig(overrides = {}) {
  const env = { ...process.env, ...overrides };

  return {
    projectRoot: PROJECT_ROOT,
    port: Number(env.PORT || 4310),
    appPassword: env.APP_PASSWORD || "2427",
    integrationSecret: env.INTEGRATION_SECRET || "change-me",
    dbPath: path.resolve(PROJECT_ROOT, env.DB_PATH || "./data/agent-board.sqlite"),
    cookieName: env.COOKIE_NAME || "agent_board_session",
    nodeEnv: env.NODE_ENV || "development",
    repoRoot: env.REPO_ROOT || "/home/dev/palomatika",
    codexHome: env.CODEX_HOME || "/home/dev/.codex"
  };
}
