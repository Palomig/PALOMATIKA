import { chromium } from '/tmp/node_modules/playwright-core/index.mjs';
import fs from 'fs';
import path from 'path';

const ROOT = '/home/dev/palomatika/public';
const REPORT_FILE = path.join(ROOT, 'class-mini-report.html');
const ASSETS_DIR = path.join(ROOT, 'class-mini-report-assets');
const BASE_MAIN = 'https://palomatika.ru';
const BASE_STUDENT = 'https://student.palomatika.ru';

const ADMIN = { email: 'qa-admin@palomatika.ru', password: 'QaTest2026!' };
const STUDENT = { email: 'qa-student@palomatika.ru', password: 'QaTest2026!' };

const VPR_GRADES = [5, 6, 7, 8];
const ALL_GRADES = [5, 6, 7, 8, 9];

fs.mkdirSync(ASSETS_DIR, { recursive: true });

function browserPath() {
  return fs.readdirSync('/home/dev/.cache/ms-playwright/')
    .filter((d) => d.startsWith('chromium-'))
    .flatMap((d) => [
      `/home/dev/.cache/ms-playwright/${d}/chrome-linux64/chrome`,
      `/home/dev/.cache/ms-playwright/${d}/chrome-linux/chrome`,
    ])
    .find((p) => fs.existsSync(p));
}

function slug(value) {
  return String(value).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

async function saveShot(page, filename) {
  const target = path.join(ASSETS_DIR, filename);
  await page.screenshot({ path: target, fullPage: true });
  return `class-mini-report-assets/${filename}`;
}

async function login(page, creds) {
  await page.goto(`${BASE_MAIN}/login`, { waitUntil: 'networkidle' });
  await page.fill('#email', creds.email);
  await page.fill('#password', creds.password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);

  if (page.url().includes('/login')) {
    throw new Error(`Login failed for ${creds.email}`);
  }
}

async function postJson(page, url, body) {
  return page.evaluate(async ({ url, body }) => {
    const xsrf = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf,
      },
      body: JSON.stringify(body),
    });

    const text = await response.text();
    let json = null;

    try {
      json = JSON.parse(text);
    } catch {
      json = null;
    }

    return {
      ok: response.ok,
      status: response.status,
      url: response.url,
      json,
      text: text.slice(0, 500),
    };
  }, { url, body });
}

async function setAdminStudentView(page, grade) {
  await page.goto(`${BASE_STUDENT}/`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  const vprButton = page.locator('form[action*="/view-as/student/exam/vpr"] button').first();
  await vprButton.click();
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(600);

  const gradeButton = page.locator(`form[action*="/view-as/student/vpr-grade/${grade}"] button`).first();
  await gradeButton.click();
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(800);
}

async function captureVprGrade(page, grade) {
  await setAdminStudentView(page, grade);
  await page.goto(`${BASE_STUDENT}/vpr`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);

  const column = {
    grade,
    title: `${grade} класс`,
    flow: 'Мини-ВПР',
    notes: [],
    shots: [],
  };

  column.shots.push({
    label: 'Дашборд',
    src: await saveShot(page, `${grade}-dashboard.png`),
  });

  const start = await postJson(page, `${BASE_STUDENT}/vpr/mini/start`, { mode: 'mixed', grade });
  if (!start.ok || !start.json?.redirect) {
    const errorText = start.json?.error || start.text || '';

    if (start.status === 422 && errorText.includes('Нет доступных заданий')) {
      column.notes.push('Mini недоступен: банк заданий пуст');
      column.notes.push('Для 7 и 8 классов это ожидаемое поведение');
    } else {
      column.notes.push(`Ошибка mini: HTTP ${start.status}`);
      if (errorText) {
        column.notes.push(errorText);
      }
    }

    column.shots.push({
      label: 'После попытки запуска mini',
      src: await saveShot(page, `${grade}-mini-error.png`),
    });

    return column;
  }

  column.notes.push(`Старт mini: HTTP ${start.status}`);

  await page.goto(start.json.redirect, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);

  column.shots.push({
    label: 'Мини-вариант',
    src: await saveShot(page, `${grade}-mini.png`),
  });

  return column;
}

async function captureOgeGrade9(page) {
  await page.goto(`${BASE_STUDENT}/`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);

  const ogeButton = page.locator('form[action*="/view-as/student/exam/oge"] button').first();
  if (await ogeButton.count()) {
    await ogeButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
  }

  await page.goto(`${BASE_STUDENT}/`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);

  const column = {
    grade: 9,
    title: '9 класс',
    flow: 'Мини-ОГЭ',
    notes: [],
    shots: [],
  };

  column.shots.push({
    label: 'Дашборд',
    src: await saveShot(page, '9-dashboard.png'),
  });

  await page.goto(`${BASE_STUDENT}/mini`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);

  column.shots.push({
    label: 'Выбор мини-варианта',
    src: await saveShot(page, '9-mini-selection.png'),
  });

  const start = await postJson(page, `${BASE_STUDENT}/mini/start`, { mode: 'mixed' });
  if (!start.ok || !start.json?.redirect) {
    column.notes.push(`Ошибка mini: HTTP ${start.status}`);
    if (start.json?.error) {
      column.notes.push(start.json.error);
    } else if (start.text) {
      column.notes.push(start.text);
    }

    column.shots.push({
      label: 'После попытки запуска mini',
      src: await saveShot(page, '9-mini-error.png'),
    });

    return column;
  }

  column.notes.push(`Старт mini: HTTP ${start.status}`);

  const target = String(start.json.redirect);
  await page.goto(target.startsWith('http') ? target : `${BASE_STUDENT}${target}`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);

  column.shots.push({
    label: 'Мини-вариант',
    src: await saveShot(page, '9-mini.png'),
  });

  return column;
}

function renderReport(columns) {
  const generatedAt = new Date().toISOString().replace('T', ' ').replace('Z', ' UTC');

  return `<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QA Mini Report by Class</title>
  <style>
    :root {
      color-scheme: light;
      --bg: #f4efe6;
      --paper: #fffaf2;
      --ink: #1d2433;
      --muted: #677286;
      --line: #d7cdbd;
      --accent: #c4682d;
      --accent-soft: #f5dfca;
      --shadow: 0 14px 40px rgba(62, 42, 24, 0.08);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Georgia", "Times New Roman", serif;
      color: var(--ink);
      background:
        radial-gradient(circle at top left, rgba(196, 104, 45, 0.10), transparent 28%),
        linear-gradient(180deg, #f8f4ed 0%, var(--bg) 100%);
    }
    .page {
      padding: 28px 20px 44px;
      max-width: 1800px;
      margin: 0 auto;
    }
    .header {
      margin-bottom: 24px;
      padding: 22px 24px;
      background: rgba(255, 250, 242, 0.86);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(135, 105, 74, 0.16);
      box-shadow: var(--shadow);
    }
    h1 {
      margin: 0 0 10px;
      font-size: 34px;
      line-height: 1.1;
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .meta {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.5;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(220px, 1fr));
      gap: 18px;
      align-items: start;
    }
    .column {
      background: rgba(255, 250, 242, 0.92);
      border: 1px solid rgba(135, 105, 74, 0.16);
      box-shadow: var(--shadow);
      padding: 16px;
    }
    .col-head {
      position: sticky;
      top: 0;
      background: linear-gradient(180deg, rgba(255,250,242,0.98), rgba(255,250,242,0.92));
      padding-bottom: 12px;
      margin-bottom: 14px;
      border-bottom: 1px solid var(--line);
    }
    .grade {
      display: inline-block;
      padding: 5px 10px;
      margin-bottom: 10px;
      background: var(--accent-soft);
      color: var(--accent);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }
    .flow {
      margin: 0;
      font-size: 22px;
      line-height: 1.15;
    }
    .note {
      margin-top: 8px;
      font-size: 13px;
      color: var(--muted);
    }
    figure {
      margin: 0 0 16px;
      padding: 0;
    }
    img {
      width: 100%;
      display: block;
      border: 1px solid var(--line);
      background: #fff;
    }
    figcaption {
      font-size: 13px;
      color: var(--muted);
      margin: 7px 2px 0;
      line-height: 1.4;
    }
    @media (max-width: 1500px) {
      .grid { grid-template-columns: repeat(3, minmax(220px, 1fr)); }
    }
    @media (max-width: 980px) {
      .grid { grid-template-columns: repeat(2, minmax(220px, 1fr)); }
    }
    @media (max-width: 640px) {
      .page { padding: 18px 12px 28px; }
      .header { padding: 18px 16px; }
      h1 { font-size: 28px; }
      .grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <section class="header">
      <h1>QA: мини-варианты по классам 5-9</h1>
      <div class="meta">${generatedAt}<br>Сервер: palomatika.ru / 78.17.28.40<br>Колонки: 5, 6, 7, 8, 9</div>
    </section>
    <section class="grid">
      ${columns.map((column) => `
        <article class="column">
          <div class="col-head">
            <div class="grade">${column.title}</div>
            <h2 class="flow">${column.flow}</h2>
            ${column.notes.map((note) => `<div class="note">${note}</div>`).join('')}
          </div>
          ${column.shots.map((shot) => `
            <figure>
              <img src="${shot.src}" alt="${column.title} ${shot.label}">
              <figcaption>${shot.label}</figcaption>
            </figure>
          `).join('')}
        </article>
      `).join('')}
    </section>
  </div>
</body>
</html>`;
}

async function main() {
  const execPath = browserPath();
  if (!execPath) {
    throw new Error('Chromium executable not found in /home/dev/.cache/ms-playwright');
  }

  const browser = await chromium.launch({
    executablePath: execPath,
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
  });

  const adminContext = await browser.newContext({
    viewport: { width: 420, height: 920 },
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
    deviceScaleFactor: 2,
  });
  const adminPage = await adminContext.newPage();

  const columns = [];

  try {
    console.log('Login as qa-admin...');
    await login(adminPage, ADMIN);

    for (const grade of VPR_GRADES) {
      console.log(`Capture VPR grade ${grade}...`);
      try {
        columns.push(await captureVprGrade(adminPage, grade));
      } catch (error) {
        columns.push({
          grade,
          title: `${grade} класс`,
          flow: 'Мини-ВПР',
          notes: [String(error?.message || error)],
          shots: [],
        });
      }
    }

    try {
      columns.push(await captureOgeGrade9(adminPage));
    } catch (error) {
      columns.push({
        grade: 9,
        title: '9 класс',
        flow: 'Мини-ОГЭ',
        notes: [String(error?.message || error)],
        shots: [],
      });
    }

    columns.sort((a, b) => a.grade - b.grade);

    fs.writeFileSync(REPORT_FILE, renderReport(columns), 'utf8');

    console.log(`Report file: ${REPORT_FILE}`);
    console.log(`Public URL: https://palomatika.ru/class-mini-report.html`);
    console.log(`Public URL (IP): https://78.17.28.40/class-mini-report.html`);
  } finally {
    await adminContext.close().catch(() => {});
    await browser.close().catch(() => {});
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
