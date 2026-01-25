#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";

const API_BASE = process.env.SMARTCART_API_URL || "https://cw95865.tmweb.ru/smartcart";

async function fetchAPI(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`;
  const resp = await fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...options.headers,
    },
  });
  return resp.json();
}

const server = new Server(
  { name: "smartcart", version: "1.0.0" },
  { capabilities: { tools: {} } }
);

// List available tools
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: "get_stats",
      description: "Получить статистику базы данных SmartCart (количество товаров, цен, рецептов)",
      inputSchema: { type: "object", properties: {} },
    },
    {
      name: "get_stores",
      description: "Получить список магазинов с количеством товаров",
      inputSchema: { type: "object", properties: {} },
    },
    {
      name: "get_categories",
      description: "Получить категории товаров для магазина",
      inputSchema: {
        type: "object",
        properties: {
          store: { type: "string", description: "Slug магазина (например: perekrestok)" },
        },
        required: ["store"],
      },
    },
    {
      name: "search_products",
      description: "Поиск товаров по названию",
      inputSchema: {
        type: "object",
        properties: {
          query: { type: "string", description: "Поисковый запрос" },
          store: { type: "string", description: "Фильтр по магазину (опционально)" },
          limit: { type: "number", description: "Максимум результатов (по умолчанию 50)" },
        },
        required: ["query"],
      },
    },
    {
      name: "get_prices",
      description: "Получить цены товаров по категории",
      inputSchema: {
        type: "object",
        properties: {
          store: { type: "string", description: "Slug магазина" },
          category: { type: "string", description: "Slug категории" },
          limit: { type: "number", description: "Максимум результатов (по умолчанию 100)" },
        },
        required: ["store"],
      },
    },
    {
      name: "compare_prices",
      description: "Сравнить цены на товар в разных магазинах",
      inputSchema: {
        type: "object",
        properties: {
          product_name: { type: "string", description: "Название товара для поиска" },
        },
        required: ["product_name"],
      },
    },
    {
      name: "get_recipes",
      description: "Получить список рецептов",
      inputSchema: {
        type: "object",
        properties: {
          limit: { type: "number", description: "Максимум результатов" },
        },
      },
    },
    {
      name: "get_cheapest",
      description: "Найти самые дешёвые товары в категории",
      inputSchema: {
        type: "object",
        properties: {
          category: { type: "string", description: "Slug категории" },
          limit: { type: "number", description: "Количество товаров (по умолчанию 20)" },
        },
        required: ["category"],
      },
    },
  ],
}));

// Handle tool calls
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case "get_stats": {
        const data = await fetchAPI("/api/stats.php");
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "get_stores": {
        const data = await fetchAPI("/api/stores.php");
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "get_categories": {
        const data = await fetchAPI(`/api/categories.php?store=${args.store}`);
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "search_products": {
        const limit = args.limit || 50;
        let url = `/api/search.php?q=${encodeURIComponent(args.query)}&limit=${limit}`;
        if (args.store) url += `&store=${args.store}`;
        const data = await fetchAPI(url);
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "get_prices": {
        const limit = args.limit || 100;
        let url = `/api/export.php?type=prices&store=${args.store}&format=compact`;
        if (args.category) url += `&categories=${args.category}`;
        const data = await fetchAPI(url);
        // Truncate if too many
        if (data.prices?.[args.store]?.length > limit) {
          data.prices[args.store] = data.prices[args.store].slice(0, limit);
          data._truncated = true;
        }
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "compare_prices": {
        const data = await fetchAPI(`/api/search.php?q=${encodeURIComponent(args.product_name)}&limit=20`);
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "get_recipes": {
        const data = await fetchAPI("/api/export.php?type=recipes");
        if (args.limit && data.recipes?.length > args.limit) {
          data.recipes = data.recipes.slice(0, args.limit);
        }
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      case "get_cheapest": {
        const limit = args.limit || 20;
        const data = await fetchAPI(`/api/cheapest.php?category=${args.category}&limit=${limit}`);
        return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
      }

      default:
        return { content: [{ type: "text", text: `Unknown tool: ${name}` }], isError: true };
    }
  } catch (error) {
    return { content: [{ type: "text", text: `Error: ${error.message}` }], isError: true };
  }
});

// Start server
const transport = new StdioServerTransport();
await server.connect(transport);
