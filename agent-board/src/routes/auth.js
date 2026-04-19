import { Router } from "express";

export function createAuthRouter({ auth }) {
  const router = Router();

  router.post("/login", (req, res) => {
    const token = auth.login(String(req.body?.password || ""));

    if (!token) {
      res.status(401).json({ error: "Invalid password" });
      return;
    }

    auth.setSessionCookie(res, token);
    res.json({ ok: true });
  });

  router.post("/logout", (req, res) => {
    auth.destroy(req);
    auth.clearSessionCookie(res);
    res.json({ ok: true });
  });

  return router;
}
