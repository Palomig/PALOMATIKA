function parseCompletedRow(row) {
  return {
    ...row,
    id: Number(row.id),
    task_id: Number(row.task_id),
    used_mcp_ids: JSON.parse(row.used_mcp_ids || "[]"),
    used_skill_ids: JSON.parse(row.used_skill_ids || "[]")
  };
}

export function listCompletedTasks(db) {
  const rows = db
    .prepare(`
      SELECT id, task_id, title, description, project, completed_by, summary, used_mcp_ids, used_skill_ids, created_at, completed_at
      FROM completed_tasks
      ORDER BY completed_at DESC, id DESC
    `)
    .all();

  return rows.map(parseCompletedRow);
}

export function completeTask(db, task, payload) {
  const completedAt = new Date().toISOString();
  const result = db.transaction(() => {
    const insertResult = db
      .prepare(`
        INSERT INTO completed_tasks (
          task_id, title, description, project, completed_by, summary, used_mcp_ids, used_skill_ids, created_at, completed_at
        ) VALUES (
          @task_id, @title, @description, @project, @completed_by, @summary, @used_mcp_ids, @used_skill_ids, @created_at, @completed_at
        )
      `)
      .run({
        task_id: task.id,
        title: task.title,
        description: task.description,
        project: task.project,
        completed_by: payload.completed_by,
        summary: payload.summary || "",
        used_mcp_ids: JSON.stringify(payload.used_mcp_ids || []),
        used_skill_ids: JSON.stringify(payload.used_skill_ids || []),
        created_at: task.created_at,
        completed_at: completedAt
      });

    db.prepare("DELETE FROM tasks WHERE id = ?").run(task.id);
    return insertResult.lastInsertRowid;
  })();

  const row = db
    .prepare(`
      SELECT id, task_id, title, description, project, completed_by, summary, used_mcp_ids, used_skill_ids, created_at, completed_at
      FROM completed_tasks
      WHERE id = ?
    `)
    .get(result);

  return parseCompletedRow(row);
}
