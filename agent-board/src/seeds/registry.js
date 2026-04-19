import fs from "node:fs";
import path from "node:path";

function walkSkillFiles(rootDir) {
  const results = [];

  if (!rootDir || !fs.existsSync(rootDir)) {
    return results;
  }

  const stack = [rootDir];

  while (stack.length > 0) {
    const current = stack.pop();
    const entries = fs.readdirSync(current, { withFileTypes: true });

    for (const entry of entries) {
      const fullPath = path.join(current, entry.name);

      if (entry.isDirectory()) {
        stack.push(fullPath);
      } else if (entry.isFile() && entry.name === "SKILL.md") {
        results.push(fullPath);
      }
    }
  }

  return results.sort();
}

function slugify(value) {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function extractDescription(skillPath) {
  try {
    const contents = fs.readFileSync(skillPath, "utf8");
    const match = contents.match(/description:\s*"([^"]+)"/);
    return match ? match[1] : "";
  } catch {
    return "";
  }
}

function normalizeSkillId(parts, usedIds) {
  let base = `skill-${slugify(parts.filter(Boolean).join("-"))}`;

  if (!base || base === "skill-") {
    base = "skill-unknown";
  }

  let nextId = base;
  let index = 2;

  while (usedIds.has(nextId)) {
    nextId = `${base}-${index}`;
    index += 1;
  }

  usedIds.add(nextId);
  return nextId;
}

function discoverSkills(codexHome) {
  const usedIds = new Set();
  const skillsRoot = path.join(codexHome, "skills");
  const pluginsRoot = path.join(codexHome, "plugins");
  const roots = [
    { root: skillsRoot, kind: "skill" },
    { root: pluginsRoot, kind: "plugin" }
  ];
  const skills = [];

  for (const source of roots) {
    for (const skillPath of walkSkillFiles(source.root)) {
      const relative = path.relative(source.root, skillPath);
      const parts = relative.split(path.sep).filter(Boolean);
      const folderName = path.basename(path.dirname(skillPath));
      const identifierParts = source.kind === "plugin"
        ? ["plugin", ...parts.slice(-3, -1)]
        : [folderName];
      const id = normalizeSkillId(identifierParts, usedIds);

      skills.push({
        id,
        name: folderName,
        description: extractDescription(skillPath),
        source_path: skillPath,
        is_active: 1
      });
    }
  }

  return skills.sort((left, right) => left.name.localeCompare(right.name));
}

function discoverMcpServers(repoRoot) {
  const configPath = path.join(repoRoot, ".mcp.json");

  if (!fs.existsSync(configPath)) {
    return [];
  }

  try {
    const payload = JSON.parse(fs.readFileSync(configPath, "utf8"));
    const servers = payload.mcpServers || {};

    return Object.entries(servers)
      .map(([name, details]) => {
        const args = Array.isArray(details.args) ? details.args.join(" ") : "";

        return {
          id: `mcp-${slugify(name)}`,
          name,
          description: [details.command, args].filter(Boolean).join(" "),
          source_path: configPath,
          is_active: 1
        };
      })
      .sort((left, right) => left.name.localeCompare(right.name));
  } catch {
    return [];
  }
}

function discoverAgents() {
  return [
    { id: "agent-jarvis", name: "Jarvis", color: "#2563eb", kind: "assistant", is_active: 1 },
    { id: "agent-claude", name: "Claude", color: "#f97316", kind: "model", is_active: 1 },
    { id: "agent-codex", name: "Codex", color: "#111111", kind: "model", is_active: 1 }
  ];
}

export function discoverRegistry({ repoRoot, codexHome }) {
  return {
    agents: discoverAgents(),
    mcpServers: discoverMcpServers(repoRoot),
    skills: discoverSkills(codexHome)
  };
}

export function syncRegistry(db, registry) {
  const replaceAgents = db.prepare(`
    INSERT OR REPLACE INTO agents (id, name, color, kind, is_active)
    VALUES (@id, @name, @color, @kind, @is_active)
  `);
  const replaceMcp = db.prepare(`
    INSERT OR REPLACE INTO mcp_servers (id, name, description, source_path, is_active)
    VALUES (@id, @name, @description, @source_path, @is_active)
  `);
  const replaceSkill = db.prepare(`
    INSERT OR REPLACE INTO skills (id, name, description, source_path, is_active)
    VALUES (@id, @name, @description, @source_path, @is_active)
  `);

  db.transaction(() => {
    db.exec("DELETE FROM agents;");
    db.exec("DELETE FROM mcp_servers;");
    db.exec("DELETE FROM skills;");

    for (const item of registry.agents) {
      replaceAgents.run(item);
    }

    for (const item of registry.mcpServers) {
      replaceMcp.run(item);
    }

    for (const item of registry.skills) {
      replaceSkill.run(item);
    }
  })();
}
