const PROJECTS = new Set(["palomatika", "evrium"]);
const COLUMNS = new Set(["unassigned", "claude", "codex"]);
const COMPLETED_BY = new Set(["claude", "codex", "manual"]);

export function validateTaskInput(payload) {
  const title = String(payload?.title || "").trim();
  const description = String(payload?.description || "").trim();
  const project = String(payload?.project || "").trim().toLowerCase();

  if (!title) {
    return { error: "Title is required" };
  }

  if (!PROJECTS.has(project)) {
    return { error: "Project must be palomatika or evrium" };
  }

  return {
    value: {
      title,
      description,
      project
    }
  };
}

export function validateMoveInput(payload) {
  const columnKey = String(payload?.column_key || "").trim().toLowerCase();

  if (!COLUMNS.has(columnKey)) {
    return { error: "column_key must be unassigned, claude, or codex" };
  }

  return { value: { column_key: columnKey } };
}

export function validateCompletionInput(payload, { requireSummary = false } = {}) {
  const summary = String(payload?.summary || "").trim();
  const completedBy = String(payload?.completed_by || "").trim().toLowerCase();
  const usedMcpIds = Array.isArray(payload?.used_mcp_ids) ? payload.used_mcp_ids.map(String) : [];
  const usedSkillIds = Array.isArray(payload?.used_skill_ids) ? payload.used_skill_ids.map(String) : [];

  if (requireSummary && !summary) {
    return { error: "Summary is required" };
  }

  if (payload?.completed_by !== undefined && !COMPLETED_BY.has(completedBy)) {
    return { error: "completed_by must be claude, codex, or manual" };
  }

  return {
    value: {
      summary,
      completed_by: completedBy,
      used_mcp_ids: usedMcpIds,
      used_skill_ids: usedSkillIds
    }
  };
}
