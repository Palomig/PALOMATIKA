import { Router } from "express";
import { requireSessionOrIntegration } from "../auth.js";
import { completeTask, listCompletedTasks } from "../repositories/completed.js";
import { validateRegistryIds } from "../repositories/registry.js";
import { findTaskById } from "../repositories/tasks.js";
import { validateCompletionInput } from "../validators/tasks.js";

export function createCompletionsRouter(context) {
  const router = Router();
  const { auth, config, db, events } = context;
  const requireUiOrIntegration = requireSessionOrIntegration(auth, config);

  router.get("/completed", auth.requireSession, (req, res) => {
    res.json({ completed: listCompletedTasks(db) });
  });

  router.post("/completions", requireUiOrIntegration, (req, res) => {
    const task = findTaskById(db, Number(req.body?.task_id));

    if (!task) {
      res.status(404).json({ error: "Task not found" });
      return;
    }

    const parsed = validateCompletionInput(req.body, { requireSummary: true });

    if (parsed.error) {
      res.status(400).json({ error: parsed.error });
      return;
    }

    if (!parsed.value.completed_by || parsed.value.completed_by === "manual") {
      res.status(400).json({ error: "completed_by must be claude or codex for API completion" });
      return;
    }

    const missingMcp = validateRegistryIds(db, "mcp_servers", parsed.value.used_mcp_ids);
    const missingSkills = validateRegistryIds(db, "skills", parsed.value.used_skill_ids);

    if (missingMcp.length > 0 || missingSkills.length > 0) {
      res.status(400).json({ error: "Unknown MCP or skill IDs", missing_mcp_ids: missingMcp, missing_skill_ids: missingSkills });
      return;
    }

    const completed = completeTask(db, task, parsed.value);
    events.broadcast("task_completed", { completed });
    res.status(201).json({ completed });
  });

  return router;
}
