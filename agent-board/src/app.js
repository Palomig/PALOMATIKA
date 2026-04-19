import express from "express";
import cookieParser from "cookie-parser";
import path from "node:path";
import { createAuth } from "./auth.js";
import { loadConfig } from "./config.js";
import { openDatabase } from "./db.js";
import { createEventBus } from "./events.js";
import { createAuthRouter } from "./routes/auth.js";
import { createCompletionsRouter } from "./routes/completions.js";
import { createEventsRouter } from "./routes/events.js";
import { createPagesRouter } from "./routes/pages.js";
import { createRegistryRouter } from "./routes/registry.js";
import { createTasksRouter } from "./routes/tasks.js";

export function createApp(options = {}) {
  const config = options.config || loadConfig();
  const db = options.db || openDatabase(config);
  const auth = options.auth || createAuth(config);
  const events = options.events || createEventBus();
  const app = express();

  app.disable("x-powered-by");
  app.use(express.json());
  app.use(express.urlencoded({ extended: false }));
  app.use(cookieParser());
  app.use("/static", express.static(path.resolve(config.projectRoot, "public")));

  app.use("/api", createAuthRouter({ auth }));
  app.use("/api/tasks", createTasksRouter({ auth, config, db, events }));
  app.use("/api", createCompletionsRouter({ auth, config, db, events }));
  app.use("/api", createRegistryRouter({ auth, db }));
  app.use("/api", createEventsRouter({ auth, events }));
  app.use(createPagesRouter({ auth, config }));

  app.use((req, res) => {
    res.status(404).json({ error: "Not found" });
  });

  app.locals.agentBoard = { config, db, auth, events };
  return app;
}
