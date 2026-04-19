export function createEventBus() {
  const clients = new Set();
  const heartbeat = setInterval(() => {
    for (const client of clients) {
      client.write(": heartbeat\n\n");
    }
  }, 15000);

  heartbeat.unref();

  function subscribe(req, res) {
    res.writeHead(200, {
      "Content-Type": "text/event-stream",
      "Cache-Control": "no-cache, no-transform",
      Connection: "keep-alive"
    });
    res.write("retry: 1000\n");
    res.write("event: connected\n");
    res.write(`data: ${JSON.stringify({ ok: true })}\n\n`);

    clients.add(res);

    req.on("close", () => {
      clients.delete(res);
    });
  }

  function broadcast(event, payload) {
    const data = `event: ${event}\ndata: ${JSON.stringify(payload)}\n\n`;

    for (const client of clients) {
      client.write(data);
    }
  }

  function close() {
    clearInterval(heartbeat);
    for (const client of clients) {
      client.end();
    }
    clients.clear();
  }

  return {
    subscribe,
    broadcast,
    close
  };
}
