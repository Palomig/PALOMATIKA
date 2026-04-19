export function listRegistry(db) {
  return {
    agents: db.prepare("SELECT id, name, color, kind, is_active FROM agents ORDER BY name ASC").all(),
    mcpServers: db.prepare("SELECT id, name, description, source_path, is_active FROM mcp_servers ORDER BY name ASC").all(),
    skills: db.prepare("SELECT id, name, description, source_path, is_active FROM skills ORDER BY name ASC").all()
  };
}

export function validateRegistryIds(db, table, ids = []) {
  if (!Array.isArray(ids) || ids.length === 0) {
    return [];
  }

  const placeholders = ids.map(() => "?").join(", ");
  const rows = db.prepare(`SELECT id FROM ${table} WHERE id IN (${placeholders})`).all(...ids);
  const found = new Set(rows.map((row) => row.id));

  return ids.filter((id) => !found.has(id));
}
