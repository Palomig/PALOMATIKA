import { Router } from "express";

export function createEventsRouter({ auth, events }) {
  const router = Router();

  router.get("/events", auth.requireSession, (req, res) => {
    events.subscribe(req, res);
  });

  return router;
}
