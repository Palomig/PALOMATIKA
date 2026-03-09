#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";

const API_BASE = process.env.PALOMATIKA_API_URL || "https://cw95865.tmweb.ru";
const SECRET = process.env.DEPLOY_WEBHOOK_SECRET || "";

async function fetchAPI(endpoint, options = {}) {
  const url = `${API_BASE}/api/deploy${endpoint}`;
  const resp = await fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      "X-Deploy-Secret": SECRET,
      ...options.headers,
    },
  });
  return resp.json();
}

const server = new Server(
  { name: "palomatika-db", version: "1.0.0" },
  { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: "list_tables",
      description: "Список всех таблиц БД Palomatika с количеством строк",
      inputSchema: { type: "object", properties: {} },
    },
    {
      name: "query",
      description:
        "Выполнить read-only SQL запрос к БД Palomatika (SELECT, SHOW, DESCRIBE, EXPLAIN). Мутации запрещены.",
      inputSchema: {
        type: "object",
        properties: {
          sql: {
            type: "string",
            description: "SQL запрос (только SELECT/SHOW/DESCRIBE/EXPLAIN)",
          },
          limit: {
            type: "number",
            description: "Максимум строк (по умолчанию 100, макс 1000)",
          },
        },
        required: ["sql"],
      },
    },
    {
      name: "describe_table",
      description: "Показать структуру таблицы (колонки, типы, ключи)",
      inputSchema: {
        type: "object",
        properties: {
          table: {
            type: "string",
            description: "Имя таблицы",
          },
        },
        required: ["table"],
      },
    },
    {
      name: "run_artisan",
      description:
        "Выполнить разрешённую artisan-команду на production сервере (deploy:refresh, cache:clear, migrate:status и др.)",
      inputSchema: {
        type: "object",
        properties: {
          command: {
            type: "string",
            description: "Artisan-команда (например: cache:clear, migrate:status)",
          },
          params: {
            type: "object",
            description: "Параметры команды (например: {\"topic\": \"16\"})",
          },
        },
        required: ["command"],
      },
    },
  ],
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case "list_tables": {
        const data = await fetchAPI("/tables");
        return {
          content: [{ type: "text", text: JSON.stringify(data, null, 2) }],
        };
      }

      case "query": {
        const data = await fetchAPI("/query", {
          method: "POST",
          body: JSON.stringify({
            sql: args.sql,
            limit: args.limit || 100,
          }),
        });
        return {
          content: [{ type: "text", text: JSON.stringify(data, null, 2) }],
        };
      }

      case "describe_table": {
        const data = await fetchAPI("/query", {
          method: "POST",
          body: JSON.stringify({
            sql: `DESCRIBE \`${args.table.replace(/`/g, "")}\``,
          }),
        });
        return {
          content: [{ type: "text", text: JSON.stringify(data, null, 2) }],
        };
      }

      case "run_artisan": {
        const data = await fetchAPI("/artisan", {
          method: "POST",
          body: JSON.stringify({
            command: args.command,
            params: args.params || {},
          }),
        });
        return {
          content: [{ type: "text", text: JSON.stringify(data, null, 2) }],
        };
      }

      default:
        return {
          content: [{ type: "text", text: `Unknown tool: ${name}` }],
          isError: true,
        };
    }
  } catch (error) {
    return {
      content: [{ type: "text", text: `Error: ${error.message}` }],
      isError: true,
    };
  }
});

const transport = new StdioServerTransport();
await server.connect(transport);
