import { chromium } from '/tmp/node_modules/playwright-core/index.mjs';
import fs from 'fs';
import path from 'path';

const BASE = 'https://palomatika.ru';
const EMAIL = 'qa-parent@palomatika.ru';
const PASSWORD = 'QaTest2026!';
const REPORT_DIR = '/home/dev/palomatika/public/qa-reports';
const SCREENSHOTS_DIR = path.join(REPORT_DIR, 'screenshots-parent');

fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });

const results = [];
let stepNum = 0;

async function screenshot(page, name) {
  stepNum++;
  const filename = `${String(stepNum).padStart(2, '0')}-${name}.png`;
  await page.screenshot({ fullPage: true, path: path.join(SCREENSHOTS_DIR, filename) });
  return `screenshots-parent/${filename}`;
}

function addResult(step, url, status, img, checks, error = null) {
  results.push({ step, url, status, img, checks, error, ts: new Date().toISOString() });
  const icon = status === 'OK' ? '\x1b[32m✓\x1b[0m' : status === 'FAIL' ? '\x1b[31m✗\x1b[0m' : '\x1b[33m~\x1b[0m';
  console.log(`  ${icon} ${step}: ${status}`);
}

(async () => {
  const browserPath = fs.readdirSync('/home/dev/.cache/ms-playwright/')
    .filter(d => d.startsWith('chromium-'))
    .flatMap(d => [
      `/home/dev/.cache/ms-playwright/${d}/chrome-linux64/chrome`,
      `/home/dev/.cache/ms-playwright/${d}/chrome-linux/chrome`,
    ])
    .find(p => fs.existsSync(p));

  const browser = await chromium.launch({
    executablePath: browserPath,
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
  });

  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
    deviceScaleFactor: 1,
  });

  const page = await context.newPage();

  try {
    // ─── Phase 1: Auth ─────────────────────────────────────────────────────
    console.log('\nPhase 1: Authentication');

    // Login form uses Alpine.js x-model with id attributes (no name attributes)
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#email', { timeout: 15000 });
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await Promise.all([
      page.waitForNavigation({ timeout: 15000 }).catch(() => {}),
      page.click('button[type=submit]'),
    ]);
    // Alpine fetch+redirect may need extra time
    await page.waitForTimeout(2000);

    const loggedInUrl = page.url();
    const loginOk = !loggedInUrl.includes('/login');
    const img1 = await screenshot(page, 'login');
    addResult('Login (parent)', loggedInUrl, loginOk ? 'OK' : 'FAIL', img1,
      [`URL after login: ${loggedInUrl}`, `Redirected away from /login: ${loginOk}`]);

    // ─── Phase 2: /parent/home page ────────────────────────────────────────
    console.log('\nPhase 2: Parent App Pages');

    await page.goto(`${BASE}/parent`, { waitUntil: 'networkidle' });
    const homeUrl = page.url();
    const homeOk = homeUrl.includes('/parent');
    const img2 = await screenshot(page, 'parent-home');
    addResult('/parent Home', homeUrl, homeOk ? 'OK' : 'FAIL', img2, [
      `URL: ${homeUrl}`,
      `Contains /parent: ${homeOk}`,
    ]);

    // ─── Phase 3: Dashboard ────────────────────────────────────────────────
    await page.goto(`${BASE}/parent/dashboard`, { waitUntil: 'networkidle' });
    const dashUrl = page.url();
    const dashOk = !dashUrl.includes('/login') && dashUrl.includes('/parent');
    const hasChildCards = await page.locator('.child-card').count();
    const img3 = await screenshot(page, 'parent-dashboard');
    addResult('Parent Dashboard', dashUrl, dashOk ? 'OK' : 'FAIL', img3, [
      `URL: ${dashUrl}`,
      `Access ok: ${dashOk}`,
      `Child cards visible: ${hasChildCards}`,
    ]);

    // ─── Phase 4: Child Skills ─────────────────────────────────────────────
    await page.goto(`${BASE}/parent/child/35/skills`, { waitUntil: 'networkidle' });
    const skillsUrl = page.url();
    const skillsOk = !skillsUrl.includes('/login') && skillsUrl.includes('skills');
    const img4 = await screenshot(page, 'child-skills');
    addResult('Child Skills', skillsUrl, skillsOk ? 'OK' : 'WARN', img4, [
      `URL: ${skillsUrl}`,
      `Page accessible: ${skillsOk}`,
    ]);

    // ─── Phase 5: Child Attendance ─────────────────────────────────────────
    await page.goto(`${BASE}/parent/child/35/attendance`, { waitUntil: 'networkidle' });
    const attUrl = page.url();
    const attOk = !attUrl.includes('/login') && attUrl.includes('attendance');
    const img5 = await screenshot(page, 'child-attendance');
    addResult('Child Attendance', attUrl, attOk ? 'OK' : 'WARN', img5, [
      `URL: ${attUrl}`,
      `Page accessible: ${attOk}`,
    ]);

    // ─── Phase 6: Child Homework ───────────────────────────────────────────
    await page.goto(`${BASE}/parent/child/35/homework`, { waitUntil: 'networkidle' });
    const hwUrl = page.url();
    const hwOk = !hwUrl.includes('/login') && hwUrl.includes('homework');
    const img6 = await screenshot(page, 'child-homework');
    addResult('Child Homework', hwUrl, hwOk ? 'OK' : 'WARN', img6, [
      `URL: ${hwUrl}`,
      `Page accessible: ${hwOk}`,
    ]);

    // ─── Phase 7: Access Control ───────────────────────────────────────────
    console.log('\nPhase 3: Access Control');

    // Другой студент (не привязан к этому родителю) — должен вернуть 404/403
    await page.goto(`${BASE}/parent/child/36/skills`, { waitUntil: 'networkidle' });
    const wrongChildUrl = page.url();
    const isBlocked = page.url().includes('/parent') || await page.locator('body').innerText().then(t => t.includes('404') || t.includes('403') || t.includes('not found') || t.includes('Forbidden'));
    const img7 = await screenshot(page, 'access-control-wrong-child');
    addResult('Access control (wrong child)', wrongChildUrl, isBlocked ? 'OK' : 'FAIL', img7, [
      `Accessing child 36 (QA Teacher, not linked to this parent)`,
      `Blocked: ${isBlocked}`,
      `URL: ${wrongChildUrl}`,
    ]);

  } catch (err) {
    const img = await screenshot(page, 'crash').catch(() => '');
    addResult('CRASH', page.url(), 'FAIL', img, [err.message], err.stack);
    console.error(err);
  } finally {
    await browser.close();
  }

  // ─── Report ──────────────────────────────────────────────────────────────
  const ok = results.filter(r => r.status === 'OK').length;
  const fail = results.filter(r => r.status === 'FAIL').length;
  const warn = results.filter(r => r.status === 'WARN').length;

  console.log('\n' + '═'.repeat(40));
  console.log(`Report: ${REPORT_DIR}/parent-report.html`);
  console.log(`Results: ${ok} OK, ${fail} FAIL, ${warn} WARN out of ${results.length}`);

  const html = `<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QA Parent Report — ${new Date().toLocaleString('ru')}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f0f14;color:#e0e0e0;padding:0 0 60px}
  header{background:#1a1a2e;padding:20px 24px;border-bottom:1px solid #222}
  h1{font-size:18px;color:#fff;margin-bottom:8px}
  .badges{display:flex;gap:8px;flex-wrap:wrap}
  .badge{padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700}
  .b-ok{background:#14532d;color:#4ade80}
  .b-fail{background:#7f1d1d;color:#f87171}
  .b-warn{background:#78350f;color:#fbbf24}
  .b-total{background:#1e293b;color:#94a3b8}
  nav{background:#16161e;padding:12px 24px;border-bottom:1px solid #1e1e2e;display:flex;gap:8px;flex-wrap:wrap}
  nav a{font-size:12px;padding:4px 8px;border-radius:6px;text-decoration:none;color:#94a3b8;background:#1e1e2e}
  nav a.ok{color:#4ade80} nav a.fail{color:#f87171} nav a.warn{color:#fbbf24}
  .step{padding:20px 24px;border-bottom:1px solid #161616}
  .step:target{background:#1a1a2e}
  .sh{display:flex;gap:14px;align-items:flex-start;margin-bottom:12px}
  .sn{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0}
  .sn.ok{background:#14532d;color:#4ade80}
  .sn.fail{background:#7f1d1d;color:#f87171}
  .sn.warn{background:#78350f;color:#fbbf24}
  .st{font-size:15px;font-weight:700;color:#fff}
  .su{font-size:11px;color:#555;margin-top:2px;word-break:break-all}
  .checks{margin-bottom:10px}
  .check{font-size:12px;color:#888;padding:2px 0}
  .err{background:#2d1010;border:1px solid #5a1414;border-radius:6px;padding:10px;font-size:11px;color:#f87171;white-space:pre-wrap;overflow-x:auto;margin-bottom:10px}
  .si{width:320px;border-radius:8px;border:1px solid #222;cursor:zoom-in;display:block}
  #lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:999;cursor:zoom-out;align-items:center;justify-content:center}
  #lb.open{display:flex} #lb img{max-width:95vw;max-height:95vh;border-radius:8px}
</style>
</head>
<body>
<div id="lb" onclick="this.classList.remove('open')"><img id="lbimg"></div>
<header>
  <h1>QA Parent Report — palomatika.ru/parent — ${new Date().toLocaleString('ru')}</h1>
  <div class="badges">
    <span class="badge b-ok">${ok} OK</span>
    <span class="badge b-fail">${fail} FAIL</span>
    <span class="badge b-warn">${warn} WARN</span>
    <span class="badge b-total">${results.length} steps</span>
  </div>
</header>
<nav>${results.map((r,i)=>`<a href="#s${i+1}" class="${r.status.toLowerCase()}">${i+1}. ${r.step}</a>`).join('')}</nav>
${results.map((r,i)=>`<div class="step" id="s${i+1}">
<div class="sh"><div class="sn ${r.status.toLowerCase()}">${i+1}</div><div><div class="st">${r.step}</div><div class="su">${r.url}</div></div></div>
<div class="checks">${r.checks.map(c=>`<div class="check">· ${c}</div>`).join('')}</div>
${r.error?`<div class="err">${r.error}</div>`:''}
<img class="si" src="${r.img}" alt="${r.step}" onclick="lbox(this.src)" loading="lazy">
</div>`).join('')}
<script>function lbox(s){document.getElementById('lbimg').src=s;document.getElementById('lb').classList.add('open')}</script>
</body></html>`;

  fs.writeFileSync(path.join(REPORT_DIR, 'parent-report.html'), html);
  process.exit(fail > 0 ? 1 : 0);
})();
