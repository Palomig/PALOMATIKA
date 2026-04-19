import fs from "node:fs";
import path from "node:path";
import Database from "better-sqlite3";
import { readFileSync } from "node:fs";
import { discoverRegistry, syncRegistry } from "./seeds/registry.js";

export function openDatabase(config) {
  fs.mkdirSync(path.dirname(config.dbPath), { recursive: true });

  const db = new Database(config.dbPath);
  db.pragma("journal_mode = WAL");
  db.pragma("foreign_keys = ON");

  const schemaPath = path.resolve(config.projectRoot, "src/schema.sql");
  db.exec(readFileSync(schemaPath, "utf8"));

  const registry = discoverRegistry({
    repoRoot: config.repoRoot,
    codexHome: config.codexHome
  });

  syncRegistry(db, registry);
  return db;
}
