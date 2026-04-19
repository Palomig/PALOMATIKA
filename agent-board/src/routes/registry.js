import { Router } from "express";
import { listRegistry } from "../repositories/registry.js";

export function createRegistryRouter({ auth, db }) {
  const router = Router();

  router.get("/registry", auth.requireSession, (req, res) => {
    res.json(listRegistry(db));
  });

  return router;
}
