import path from "node:path";
import { Router } from "express";

export function createPagesRouter({ auth, config }) {
  const router = Router();
  const viewsRoot = path.resolve(config.projectRoot, "views");

  router.get("/login", (req, res) => {
    if (auth.isAuthenticated(req)) {
      res.redirect("/");
      return;
    }

    res.sendFile(path.join(viewsRoot, "login.html"));
  });

  router.get("/", auth.requireSession, (req, res) => {
    res.sendFile(path.join(viewsRoot, "board.html"));
  });

  router.get("/completed", auth.requireSession, (req, res) => {
    res.sendFile(path.join(viewsRoot, "completed.html"));
  });

  return router;
}
