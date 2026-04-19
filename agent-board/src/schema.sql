CREATE TABLE IF NOT EXISTS tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  project TEXT NOT NULL CHECK (project IN ('palomatika', 'evrium')),
  column_key TEXT NOT NULL CHECK (column_key IN ('unassigned', 'claude', 'codex')),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_tasks_column_key ON tasks (column_key);
CREATE INDEX IF NOT EXISTS idx_tasks_project ON tasks (project);

CREATE TABLE IF NOT EXISTS completed_tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  task_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  project TEXT NOT NULL CHECK (project IN ('palomatika', 'evrium')),
  completed_by TEXT NOT NULL CHECK (completed_by IN ('claude', 'codex', 'manual')),
  summary TEXT NOT NULL DEFAULT '',
  used_mcp_ids TEXT NOT NULL DEFAULT '[]',
  used_skill_ids TEXT NOT NULL DEFAULT '[]',
  created_at TEXT NOT NULL,
  completed_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_completed_completed_at ON completed_tasks (completed_at DESC);
CREATE INDEX IF NOT EXISTS idx_completed_project ON completed_tasks (project);

CREATE TABLE IF NOT EXISTS agents (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  color TEXT NOT NULL DEFAULT '',
  kind TEXT NOT NULL DEFAULT 'agent',
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS mcp_servers (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  source_path TEXT NOT NULL DEFAULT '',
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS skills (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  source_path TEXT NOT NULL DEFAULT '',
  is_active INTEGER NOT NULL DEFAULT 1
);
