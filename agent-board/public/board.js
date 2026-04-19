const COLUMN_KEYS = ["unassigned", "claude", "codex"];

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

function agentLabel(columnKey) {
  switch (columnKey) {
    case "claude":
      return "Claude";
    case "codex":
      return "Codex";
    default:
      return "Без агента";
  }
}

export function groupTasksByColumn(tasks) {
  const grouped = {
    unassigned: [],
    claude: [],
    codex: []
  };

  for (const task of tasks) {
    const key = COLUMN_KEYS.includes(task.column_key) ? task.column_key : "unassigned";
    grouped[key].push(task);
  }

  return grouped;
}

function renderEmptyState(message) {
  return `<div class="empty-state">${escapeHtml(message)}</div>`;
}

function renderTaskCard(task) {
  return `
    <article class="task-card" draggable="true" data-task-id="${task.id}">
      <div class="task-meta">
        <span class="id-pill">task-${task.id}</span>
        <span class="agent-pill agent-pill-${task.column_key}">${escapeHtml(agentLabel(task.column_key))}</span>
      </div>
      <div>
        <h3>${escapeHtml(task.title)}</h3>
        <p class="task-description">${escapeHtml(task.description || "Без описания")}</p>
      </div>
      <div class="task-footer">
        <span class="project-pill project-${task.project}">${escapeHtml(projectLabel(task.project))}</span>
        <span class="meta-text">${new Date(task.created_at).toLocaleString("ru-RU")}</span>
      </div>
      <button class="task-action" type="button" data-complete-task="${task.id}">Завершить</button>
    </article>
  `;
}

function renderRegistryItems(items, type) {
  if (!items || items.length === 0) {
    return renderEmptyState("Пока пусто");
  }

  return items
    .map((item) => `
      <article class="registry-item">
        <div class="registry-item-top">
          <div>
            <div class="id-pill">${escapeHtml(item.id)}</div>
          </div>
          <button class="copy-button" type="button" data-copy-id="${escapeHtml(item.id)}">Copy ID</button>
        </div>
        <div>
          <h3>${escapeHtml(item.name)}</h3>
          <p class="registry-description">${escapeHtml(item.description || (type === "agents" ? item.kind : "Без описания"))}</p>
        </div>
      </article>
    `)
    .join("");
}

async function api(fetchImpl, url, options = {}) {
  const response = await fetchImpl(url, {
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {})
    },
    ...options
  });

  const isJson = (response.headers.get("content-type") || "").includes("application/json");
  const payload = isJson ? await response.json() : null;

  if (!response.ok) {
    throw new Error(payload?.error || `Request failed: ${response.status}`);
  }

  return payload;
}

export function createBoardController({
  document,
  fetchImpl = window.fetch.bind(window),
  EventSourceImpl = window.EventSource,
  navigatorImpl = window.navigator
}) {
  const root = document.getElementById("board-page");

  if (!root) {
    return null;
  }

  const state = {
    tasks: [],
    registry: {
      agents: [],
      mcpServers: [],
      skills: []
    },
    eventSource: null,
    dragTaskId: null
  };

  const liveIndicator = document.getElementById("live-indicator");
  const form = document.getElementById("task-form");
  const formError = document.getElementById("task-form-error");
  const agentsList = document.getElementById("agents-list");
  const mcpList = document.getElementById("mcp-list");
  const skillsList = document.getElementById("skills-list");

  function setLiveState(mode, text) {
    liveIndicator.className = `status-chip status-${mode}`;
    liveIndicator.textContent = text;
  }

  function renderBoard() {
    const grouped = groupTasksByColumn(state.tasks);

    for (const key of COLUMN_KEYS) {
      const lane = document.getElementById(`column-${key}`);
      const count = document.getElementById(`count-${key}`);
      const items = grouped[key];
      count.textContent = String(items.length);
      lane.innerHTML = items.length > 0 ? items.map(renderTaskCard).join("") : renderEmptyState("Перетащи сюда карточку или добавь новую задачу.");
    }
  }

  function renderRegistry() {
    agentsList.innerHTML = renderRegistryItems(state.registry.agents, "agents");
    mcpList.innerHTML = renderRegistryItems(state.registry.mcpServers, "mcp");
    skillsList.innerHTML = renderRegistryItems(state.registry.skills, "skills");
  }

  async function loadTasks() {
    const payload = await api(fetchImpl, "/api/tasks");
    state.tasks = payload.tasks;
    renderBoard();
  }

  async function loadRegistry() {
    state.registry = await api(fetchImpl, "/api/registry");
    renderRegistry();
  }

  async function loadAll() {
    await Promise.all([loadTasks(), loadRegistry()]);
  }

  async function submitTask(formData) {
    await api(fetchImpl, "/api/tasks", {
      method: "POST",
      body: JSON.stringify({
        title: formData.get("title"),
        description: formData.get("description"),
        project: formData.get("project")
      })
    });
    form.reset();
    formError.hidden = true;
    await loadTasks();
  }

  async function moveTask(taskId, columnKey) {
    await api(fetchImpl, `/api/tasks/${taskId}/move`, {
      method: "PATCH",
      body: JSON.stringify({ column_key: columnKey })
    });
    await loadTasks();
  }

  async function completeTask(taskId) {
    await api(fetchImpl, `/api/tasks/${taskId}/complete`, {
      method: "POST",
      body: JSON.stringify({})
    });
    await loadTasks();
  }

  async function logout() {
    await api(fetchImpl, "/api/logout", { method: "POST", body: JSON.stringify({}) });
    window.location.href = "/login";
  }

  function bindDnD() {
    root.addEventListener("dragstart", (event) => {
      const card = event.target.closest(".task-card");

      if (!card) {
        return;
      }

      state.dragTaskId = card.dataset.taskId;
      card.classList.add("dragging");
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", state.dragTaskId);
    });

    root.addEventListener("dragend", (event) => {
      const card = event.target.closest(".task-card");
      state.dragTaskId = null;

      if (card) {
        card.classList.remove("dragging");
      }

      document.querySelectorAll(".card-lane").forEach((lane) => lane.classList.remove("drag-target"));
    });

    document.querySelectorAll(".card-lane").forEach((lane) => {
      lane.addEventListener("dragover", (event) => {
        event.preventDefault();
        lane.classList.add("drag-target");
      });

      lane.addEventListener("dragleave", () => {
        lane.classList.remove("drag-target");
      });

      lane.addEventListener("drop", async (event) => {
        event.preventDefault();
        lane.classList.remove("drag-target");
        const taskId = state.dragTaskId || event.dataTransfer.getData("text/plain");
        const columnKey = lane.id.replace("column-", "");

        if (!taskId || !COLUMN_KEYS.includes(columnKey)) {
          return;
        }

        await moveTask(taskId, columnKey);
      });
    });
  }

  function bindActions() {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      try {
        await submitTask(new FormData(form));
      } catch (error) {
        formError.hidden = false;
        formError.textContent = error.message;
      }
    });

    root.addEventListener("click", async (event) => {
      const completeButton = event.target.closest("[data-complete-task]");

      if (completeButton) {
        await completeTask(completeButton.dataset.completeTask);
        return;
      }

      const copyButton = event.target.closest("[data-copy-id]");

      if (copyButton) {
        const value = copyButton.dataset.copyId;

        if (navigatorImpl?.clipboard?.writeText) {
          await navigatorImpl.clipboard.writeText(value);
          copyButton.textContent = "Copied";
          setTimeout(() => {
            copyButton.textContent = "Copy ID";
          }, 1200);
        }
      }
    });

    document.getElementById("logout-button").addEventListener("click", logout);
  }

  function connectEvents() {
    const eventSource = new EventSourceImpl("/api/events");
    state.eventSource = eventSource;
    setLiveState("waiting", "Live: connecting");

    eventSource.onopen = () => {
      setLiveState("live", "Live: connected");
    };

    eventSource.onerror = () => {
      setLiveState("offline", "Live: reconnecting");
    };

    ["task_created", "task_moved", "task_completed"].forEach((eventName) => {
      eventSource.addEventListener(eventName, () => {
        loadTasks().catch(() => {
          setLiveState("offline", "Live: sync failed");
        });
      });
    });

    eventSource.addEventListener("registry_updated", () => {
      loadRegistry().catch(() => {
        setLiveState("offline", "Live: registry failed");
      });
    });
  }

  async function init() {
    bindDnD();
    bindActions();
    await loadAll();
    connectEvents();
  }

  return {
    state,
    init,
    loadAll,
    moveTask,
    completeTask,
    renderBoard,
    renderRegistry
  };
}

if (typeof document !== "undefined" && document.getElementById("board-page")) {
  const controller = createBoardController({ document });
  controller?.init().catch((error) => {
    console.error(error);
  });
}
