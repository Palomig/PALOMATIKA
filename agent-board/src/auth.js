import crypto from "node:crypto";

export function createAuth(config) {
  const sessions = new Map();

  function getSessionToken(req) {
    return req.cookies?.[config.cookieName] || null;
  }

  function isAuthenticated(req) {
    const token = getSessionToken(req);
    return token ? sessions.has(token) : false;
  }

  function createSession() {
    const token = crypto.randomUUID();
    sessions.set(token, { createdAt: Date.now() });
    return token;
  }

  function login(password) {
    if (password !== config.appPassword) {
      return null;
    }

    return createSession();
  }

  function destroy(req) {
    const token = getSessionToken(req);

    if (token) {
      sessions.delete(token);
    }
  }

  function setSessionCookie(res, token) {
    res.cookie(config.cookieName, token, {
      httpOnly: true,
      sameSite: "lax",
      secure: config.nodeEnv === "production",
      path: "/"
    });
  }

  function clearSessionCookie(res) {
    res.clearCookie(config.cookieName, {
      httpOnly: true,
      sameSite: "lax",
      secure: config.nodeEnv === "production",
      path: "/"
    });
  }

  function requireSession(req, res, next) {
    if (!isAuthenticated(req)) {
      if (req.path.startsWith("/api/")) {
        res.status(401).json({ error: "Authentication required" });
        return;
      }

      res.redirect("/login");
      return;
    }

    next();
  }

  return {
    sessions,
    login,
    destroy,
    isAuthenticated,
    requireSession,
    setSessionCookie,
    clearSessionCookie
  };
}

export function hasValidIntegrationSecret(req, config) {
  return req.get("X-Agent-Board-Secret") === config.integrationSecret;
}

export function requireSessionOrIntegration(auth, config) {
  return (req, res, next) => {
    if (auth.isAuthenticated(req) || hasValidIntegrationSecret(req, config)) {
      next();
      return;
    }

    res.status(401).json({ error: "Authentication or integration secret required" });
  };
}
