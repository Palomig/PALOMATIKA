function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function projectLabel(project) {
  return project === "evrium" ? "Evrium" : "Palomatika";
}

function renderTags(items) {
  if (!items || items.length === 0) {
    return '<span class="meta-text">not provided</span>';
  }

  return `<div class="inline-list">${items.map((item) => `<span class="inline-tag">${escapeHtml(item)}</span>`).join("")}</div>`;
}

export function filterCompletedEntries(entries, { project = "all", query = "" } = {}) {
  const normalizedQuery = query.trim().toLowerCase();

  return entries.filter((entry) => {
    const projectMatches = project === "all" || entry.project === project;
    const haystack = [
      entry.title,
      entry.description,
      entry.summary,
      entry.completed_by,
      `task-${entry.task_id}`,
      ...(entry.used_mcp_ids || []),
      ...(entry.used_skill_ids || [])
    ]
      .join(" ")
      .toLowerCase();

    const queryMatches = !normalizedQuery || haystack.includes(normalizedQuery);
    return projectMatches && queryMatches;
  });
}

function renderEntry(entry) {
  return `
    <article class="completed-entry">
      <div class="task-meta">
        <div class="inline-list">
          <span class="id-pill">task-${entry.task_id}</span>
          <span class="project-pill project-${entry.project}">${escapeHtml(projectLabel(entry.project))}</span>
          <span class="agent-pill agent-pill-${entry.completed_by}">${escapeHtml(entry.completed_by)}</span>
        </div>
        <span class="meta-text">${new Date(entry.completed_at).toLocaleString("ru-RU")}</span>
      </div>

      <div>
        <h2>${escapeHtml(entry.title)}</h2>
        <p class="task-description">${escapeHtml(entry.description || "Без описания")}</p>
      </div>

      <div class="completed-grid">
        <section class="completed-block">
          <strong>Summary</strong>
          <p>${escapeHtml(entry.summary || "not provided")}</p>
        </section>
        <section class="completed-block">
          <strong>Agent</strong>
          <p>${escapeHtml(entry.completed_by)}</p>
        </section>
        <section class="completed-block">
          <strong>MCP Servers</strong>
          ${renderTags(entry.used_mcp_ids)}
        </section>
        <section class="completed-block">
          <strong>Skills</strong>
          ${renderTags(entry.used_skill_ids)}
        </section>
      </div>
    </article>
  `;
}

async function api(fetchImpl, url) {
  const response = await fetchImpl(url);
  const payload = await response.json();

  if (!response.ok) {
    throw new Error(payload?.error || `Request failed: ${response.status}`);
  }

  return payload;
}

export function createCompletedController({
  document,
  fetchImpl = window.fetch.bind(window),
  EventSourceImpl = window.EventSource
}) {
  const root = document.getElementById("completed-page");

  if (!root) {
    return null;
  }

  const state = {
    entries: []
  };

  const list = document.getElementById("completed-list");
  const projectFilter = document.getElementById("project-filter");
  const searchFilter = document.getElementById("search-filter");
  const liveIndicator = document.getElementById("completed-live-indicator");

  function setLiveState(mode, text) {
    liveIndicator.className = `status-chip status-${mode}`;
    liveIndicator.textContent = text;
  }

  function render() {
    const visible = filterCompletedEntries(state.entries, {
      project: projectFilter.value,
      query: searchFilter.value
    });

    list.innerHTML = visible.length > 0
      ? visible.map(renderEntry).join("")
      : '<div class="empty-state">Пока нет завершенных задач.</div>';
  }

  async function loadCompleted() {
    const payload = await api(fetchImpl, "/api/completed");
    state.entries = payload.completed;
    render();
  }

  function bindEvents() {
    projectFilter.addEventListener("change", render);
    searchFilter.addEventListener("input", render);
    document.getElementById("logout-button").addEventListener("click", async () => {
      await fetchImpl("/api/logout", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({})
      });
      window.location.href = "/login";
    });
  }

  function connectEvents() {
    const source = new EventSourceImpl("/api/events");
    setLiveState("waiting", "Live: connecting");

    source.onopen = () => {
      setLiveState("live", "Live: connected");
    };

    source.onerror = () => {
      setLiveState("offline", "Live: reconnecting");
    };

    source.addEventListener("task_completed", () => {
      loadCompleted().catch(() => {
        setLiveState("offline", "Live: sync failed");
      });
    });
  }

  async function init() {
    bindEvents();
    await loadCompleted();
    connectEvents();
  }

  return {
    state,
    init,
    render
  };
}

if (typeof document !== "undefined" && document.getElementById("completed-page")) {
  const controller = createCompletedController({ document });
  controller?.init().catch((error) => {
    console.error(error);
  });
}
