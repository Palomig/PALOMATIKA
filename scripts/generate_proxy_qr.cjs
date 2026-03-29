const fs = require("fs");
const path = require("path");
const QRCode = require("/home/dev/palomatika/.codex-tmp/qr-builder/node_modules/qrcode");

const proxyUrl =
  "tg://proxy?server=193.23.3.134&port=8444&secret=d58de839d4d522103d715ddb40c1a59d";

const outputDir = path.join("/home/dev/palomatika/public/proxy");
const assetsDir = path.join(outputDir, "assets");
const svgPath = path.join(assetsDir, "telegram-proxy-qr.svg");
const htmlPath = path.join(outputDir, "index.html");

const qr = QRCode.create(proxyUrl, {
  errorCorrectionLevel: "H",
  margin: 0,
});

const size = qr.modules.size;
const data = qr.modules.data;
const cell = 18;
const quiet = 4;
const total = (size + quiet * 2) * cell;
const center = quiet + size / 2;
const cutoutSize = 6;
const cutoutHalf = cutoutSize / 2;

function isDark(row, col) {
  return data[row * size + col];
}

function inFinder(row, col) {
  const zones = [
    { r: 0, c: 0 },
    { r: 0, c: size - 7 },
    { r: size - 7, c: 0 },
  ];

  return zones.some(({ r, c }) => row >= r && row < r + 7 && col >= c && col < c + 7);
}

function inLogoCutout(row, col) {
  const r = row + quiet + 0.5;
  const c = col + quiet + 0.5;
  return (
    r > center - cutoutHalf &&
    r < center + cutoutHalf &&
    c > center - cutoutHalf &&
    c < center + cutoutHalf
  );
}

function moduleRect(row, col) {
  const x = (col + quiet) * cell;
  const y = (row + quiet) * cell;
  const radius = cell * 0.18;
  return `<rect x="${x + cell * 0.16}" y="${y + cell * 0.16}" width="${cell * 0.68}" height="${cell * 0.68}" rx="${radius}" fill="url(#dotGradient)" />`;
}

function finderBlock(rowOffset, colOffset) {
  const x = (colOffset + quiet) * cell;
  const y = (rowOffset + quiet) * cell;
  const outer = 7 * cell;
  const middle = 5 * cell;
  const inner = 3 * cell;

  return `
    <g filter="url(#finderGlow)">
      <rect x="${x}" y="${y}" width="${outer}" height="${outer}" rx="${cell * 0.9}" fill="#101828" />
      <rect x="${x + cell}" y="${y + cell}" width="${middle}" height="${middle}" rx="${cell * 0.62}" fill="#ffffff" />
      <rect x="${x + cell * 2}" y="${y + cell * 2}" width="${inner}" height="${inner}" rx="${cell * 0.36}" fill="#101828" />
    </g>
  `;
}

const modules = [];
for (let row = 0; row < size; row += 1) {
  for (let col = 0; col < size; col += 1) {
    if (!isDark(row, col)) continue;
    if (inFinder(row, col)) continue;
    if (inLogoCutout(row, col)) continue;
    modules.push(moduleRect(row, col));
  }
}

const logoDiameter = cell * 5.6;
const logoX = total / 2;
const logoY = total / 2;

const logoSvg = `
  <g transform="translate(${logoX}, ${logoY})">
    <circle r="${logoDiameter / 2 + 8}" fill="#ffffff" />
    <circle r="${logoDiameter / 2}" fill="url(#logoGradient)" />
    <path d="M-14.8 3 28.3-13.9c1.9-.7 3.8 1.1 3.2 3L15 35.9c-.7 2.1-3.7 2.3-4.6.3L3.1 21l-15.7-5.8c-2.5-.9-2.4-4.4.1-5.4l9-3.5 12 9.5c.8.7 2.1-.3 1.5-1.3L.8.7-14.8 3Z" fill="#ffffff" />
  </g>
`;

const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${total}" height="${total}" viewBox="0 0 ${total} ${total}" fill="none">
  <defs>
    <linearGradient id="bgGradient" x1="0" y1="0" x2="${total}" y2="${total}" gradientUnits="userSpaceOnUse">
      <stop stop-color="#ffffff" />
      <stop offset="1" stop-color="#f7faff" />
    </linearGradient>
    <linearGradient id="dotGradient" x1="${cell * quiet}" y1="${cell * quiet}" x2="${total - cell * quiet}" y2="${total - cell * quiet}" gradientUnits="userSpaceOnUse">
      <stop stop-color="#0f172a" />
      <stop offset="1" stop-color="#0f172a" />
    </linearGradient>
    <linearGradient id="logoGradient" x1="-20" y1="-20" x2="22" y2="26" gradientUnits="userSpaceOnUse">
      <stop stop-color="#18c6ff" />
      <stop offset="1" stop-color="#4a7fff" />
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="14" stdDeviation="18" flood-color="#000000" flood-opacity="0.16" />
    </filter>
    <filter id="finderGlow" x="-40%" y="-40%" width="180%" height="180%">
      <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#60a5fa" flood-opacity="0.12" />
    </filter>
    <clipPath id="qrClip">
      <rect x="0" y="0" width="${total}" height="${total}" rx="${cell * 2.2}" />
    </clipPath>
  </defs>

  <g filter="url(#shadow)">
    <rect width="${total}" height="${total}" rx="${cell * 2.2}" fill="url(#bgGradient)" />
    <rect x="${cell * 0.7}" y="${cell * 0.7}" width="${total - cell * 1.4}" height="${total - cell * 1.4}" rx="${cell * 1.7}" stroke="rgba(96,165,250,0.12)" stroke-width="2" />
    <g clip-path="url(#qrClip)">
      ${finderBlock(0, 0)}
      ${finderBlock(0, size - 7)}
      ${finderBlock(size - 7, 0)}
      ${modules.join("\n")}
      ${logoSvg}
    </g>
  </g>
</svg>
`;

const html = `<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Подключить MTProxy | PALOMATIKA</title>
  <meta name="description" content="Откройте Telegram и подключите MTProxy PALOMATIKA через QR-код или кнопку.">
  <style>
    :root {
      --page: #08111f;
      --card: #0f1b2f;
      --card-border: rgba(118, 152, 216, 0.16);
      --text: #edf4ff;
      --muted: #9ab0d3;
      --button: #13233d;
      --button-text: #7fd2ff;
      --shadow: rgba(0, 0, 0, 0.34);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      overflow: hidden;
      font-family: "Trebuchet MS", "Segoe UI", sans-serif;
      color: var(--text);
      background: var(--page);
    }

    .card {
      width: min(92vw, 480px);
      padding: 28px 28px 24px;
      border: 1px solid var(--card-border);
      border-radius: 32px;
      background: var(--card);
      box-shadow: 0 30px 80px var(--shadow);
      text-align: center;
    }

    .brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 8px 14px;
      border-radius: 999px;
      background: #12233f;
      color: #7ecfff;
      font-size: 14px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .brand-mark {
      width: 26px;
      height: 26px;
      display: inline-grid;
      place-items: center;
      border-radius: 50%;
      background: #19304f;
      color: #119af5;
      font-size: 14px;
      font-weight: 700;
    }

    h1 {
      margin: 18px 0 10px;
      font-size: clamp(32px, 7vw, 50px);
      line-height: 0.94;
      letter-spacing: -0.04em;
    }

    p {
      margin: 0 auto;
      max-width: 32ch;
      color: var(--muted);
      font-size: 16px;
      line-height: 1.55;
    }

    .qr-shell {
      position: relative;
      margin: 26px auto 22px;
      width: min(72vw, 340px);
      aspect-ratio: 1;
      display: grid;
      place-items: center;
    }

    .qr {
      width: 100%;
      max-width: 320px;
      filter: drop-shadow(0 18px 28px rgba(17, 24, 39, 0.12));
    }

    .action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 58px;
      padding: 16px 22px;
      border-radius: 18px;
      background: var(--button);
      color: var(--button-text);
      text-decoration: none;
      font-size: 18px;
      font-weight: 700;
      border: 1px solid rgba(127, 210, 255, 0.18);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.22);
      transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .action:hover {
      transform: translateY(-2px);
      box-shadow: 0 18px 30px rgba(17, 24, 39, 0.12);
    }

    .hint {
      margin-top: 14px;
      font-size: 14px;
      color: #9ab0d3;
    }

    .code {
      margin-top: 18px;
      padding: 14px 16px;
      border-radius: 18px;
      background: #12203a;
      color: #95acd2;
      font-size: 12px;
      line-height: 1.45;
      word-break: break-all;
    }

    @media (max-width: 520px) {
      .card {
        padding: 22px 18px 18px;
        border-radius: 26px;
      }

      .qr-shell {
        width: min(82vw, 300px);
      }
    }
  </style>
</head>
<body>
  <main class="card">
    <div class="brand">
      <span class="brand-mark">T</span>
      <span>PALOMATIKA Proxy</span>
    </div>
    <h1>Открой Telegram<br>в один тап</h1>
    <p>Сканируйте QR-код камерой или нажмите кнопку ниже. Ссылка ведёт напрямую в приложение Telegram и предлагает подключить прокси.</p>

    <div class="qr-shell">
      <img class="qr" src="./assets/telegram-proxy-qr.svg" alt="QR-код для подключения MTProxy в Telegram">
    </div>

    <a class="action" href="${proxyUrl}">Открыть в Telegram</a>
    <div class="hint">Если кнопка не сработала в браузере, откройте QR-код камерой телефона.</div>
    <div class="code">${proxyUrl}</div>
  </main>
</body>
</html>
`;

fs.mkdirSync(assetsDir, { recursive: true });
fs.writeFileSync(svgPath, svg, "utf8");
fs.writeFileSync(htmlPath, html, "utf8");

console.log(`Generated ${svgPath}`);
console.log(`Generated ${htmlPath}`);
