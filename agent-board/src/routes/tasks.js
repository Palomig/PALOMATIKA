import { Router } from "express";
import { requireSessionOrIntegration } from "../auth.js";
import { completeTask } from "../repositories/completed.js";
import { validateRegistryIds } from "../repositories/registry.js";
import { createTask, findTaskById, listTasks, moveTask } from "../repositories/tasks.js";
import { validateCompletionInput, validateMoveInput, validateTaskInput } from "../validators/tasks.js";

function manualCompletionAgent(task) {
  if (task.column_key === "claude" || task.column_key === "codex") {
    return task.column_key;
  }

  return "manual";
}

export function createTasksRouter(context) {
  const router = Router();
  const { auth, config, db, events } = context;
  const requireUiOrIntegration = requireSessionOrIntegration(auth, config);

  router.get("/", auth.requireSession, (req, res) => {
    res.json({ tasks: listTasks(db) });
  });

  router.post("/", requireUiOrIntegration, (req, res) => {
    const parsed = validateTaskInput(req.body);

    if (parsed.error) {
      res.status(400).json({ error: parsed.error });
      return;
    }

    const task = createTask(db, parsed.value);
    events.broadcast("task_created", { task });
    res.status(201).json({ task });
  });

  router.patch("/:id/move", auth.requireSession, (req, res) => {
    const parsed = validateMoveInput(req.body);

    if (parsed.error) {
      res.status(400).json({ error: parsed.error });
      return;
    }

    const task = moveTask(db, Number(req.params.id), parsed.value.column_key);

    if (!task) {
      res.status(404).json({ error: "Task not found" });
      return;
    }

    events.broadcast("task_moved", { task });
    res.json({ task });
  });

  router.post("/:id/complete", auth.requireSession, (req, res) => {
    const task = findTaskById(db, Number(req.params.id));

    if (!task) {
      res.status(404).json({ error: "Task not found" });
      return;
    }

    const parsed = validateCompletionInput(req.body);

    if (parsed.error) {
      res.status(400).json({ error: parsed.error });
      return;
    }

    const missingMcp = validateRegistryIds(db, "mcp_servers", parsed.value.used_mcp_ids);
    const missingSkills = validateRegistryIds(db, "skills", parsed.value.used_skill_ids);

    if (missingMcp.length > 0 || missingSkills.length > 0) {
      res.status(400).json({ error: "Unknown MCP or skill IDs", missing_mcp_ids: missingMcp, missing_skill_ids: missingSkills });
      return;
    }

    const completed = completeTask(db, task, {
      ...parsed.value,
      completed_by: manualCompletionAgent(task)
    });

    events.broadcast("task_completed", { completed });
    res.json({ completed });
  });

  return router;
}
