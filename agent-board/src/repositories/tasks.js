function parseTask(row) {
  return {
    ...row,
    id: Number(row.id)
  };
}

export function listTasks(db) {
  const rows = db
    .prepare("SELECT id, title, description, project, column_key, created_at, updated_at FROM tasks ORDER BY updated_at DESC, id DESC")
    .all();

  return rows.map(parseTask);
}

export function createTask(db, payload) {
  const now = new Date().toISOString();
  const result = db
    .prepare(`
      INSERT INTO tasks (title, description, project, column_key, created_at, updated_at)
      VALUES (@title, @description, @project, 'unassigned', @created_at, @updated_at)
    `)
    .run({
      title: payload.title,
      description: payload.description || "",
      project: payload.project,
      created_at: now,
      updated_at: now
    });

  return findTaskById(db, result.lastInsertRowid);
}

export function findTaskById(db, id) {
  const row = db
    .prepare("SELECT id, title, description, project, column_key, created_at, updated_at FROM tasks WHERE id = ?")
    .get(id);

  return row ? parseTask(row) : null;
}

export function moveTask(db, id, columnKey) {
  const now = new Date().toISOString();
  const result = db
    .prepare("UPDATE tasks SET column_key = ?, updated_at = ? WHERE id = ?")
    .run(columnKey, now, id);

  if (result.changes === 0) {
    return null;
  }

  return findTaskById(db, id);
}

export function deleteTask(db, id) {
  return db.prepare("DELETE FROM tasks WHERE id = ?").run(id);
}
