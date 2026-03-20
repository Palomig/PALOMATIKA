import { chromium } from '/tmp/node_modules/playwright-core/index.mjs';
import fs from 'fs';
import path from 'path';

const BASE = 'https://palomatika.ru';
const EMAIL = 'qa-student@palomatika.ru';
const PASSWORD = 'QaTest2026!';
const REPORT_DIR = '/home/dev/palomatika/public/qa-reports';
const SCREENSHOTS_DIR = path.join(REPORT_DIR, 'screenshots');

fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });

const results = [];
let stepNum = 0;

async function screenshot(page, name) {
  stepNum++;
  const filename = `${String(stepNum).padStart(2, '0')}-${name}.png`;
  await page.screenshot({ fullPage: true, path: path.join(SCREENSHOTS_DIR, filename) });
  return `screenshots/${filename}`;
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
    deviceScaleFactor: 2,
  });

  const page = await context.newPage();
  let attemptId = null;

  try {
    // ── 1. Login ──
    console.log('Phase 1: Authentication');
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    let img = await screenshot(page, 'login-page');

    // Alpine.js form — fill via #id selectors
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await screenshot(page, 'login-filled');

    // Submit via Alpine — click the submit button
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    img = await screenshot(page, 'after-login');

    const loggedInUrl = page.url();
    const loginOk = !loggedInUrl.includes('/login');
    addResult('Login', loggedInUrl, loginOk ? 'OK' : 'FAIL', img, [
      loginOk ? `Redirected to: ${loggedInUrl}` : 'Still on login page — auth failed',
    ]);

    if (!loginOk) {
      throw new Error('Login failed — cannot proceed with QA');
    }

    // ── 2. /tg home ──
    console.log('\nPhase 2: Navigation');
    await page.goto(`${BASE}/tg`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'tg-home');
    const tgUrl = page.url();
    addResult('/tg Home', tgUrl, tgUrl.includes('dashboard') ? 'OK' : 'WARN', img, [
      `Final URL: ${tgUrl}`,
    ]);

    // ── 3. Dashboard ──
    await page.goto(`${BASE}/tg/dashboard`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'dashboard');
    const dashUrl = page.url();
    const dashOk = !dashUrl.includes('login') && !dashUrl.includes('onboarding');
    addResult('Dashboard', dashUrl, dashOk ? 'OK' : 'FAIL', img, [
      dashOk ? 'Dashboard loaded' : `Redirected away: ${dashUrl}`,
      await page.locator('text=Мини-ОГЭ').count() > 0 ? '"Мини-ОГЭ" found' : 'MISSING: Мини-ОГЭ button',
      await page.locator('a[href*="history"]').count() > 0 ? 'History link found' : 'No history link visible',
      await page.locator('a[href*="profile"]').count() > 0 ? 'Profile link found' : 'No profile link visible',
    ]);

    // ── 4. Mini selection ──
    await page.goto(`${BASE}/tg/mini`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'mini-selection');
    const miniChecks = [];
    for (const label of ['Алгебра', 'Геометрия', 'Смешанн', '2 часть']) {
      const found = await page.locator(`text=${label}`).count() > 0;
      miniChecks.push(found ? `"${label}" found` : `MISSING: "${label}"`);
    }
    addResult('Mini OGE Selection', page.url(), 'OK', img, miniChecks);

    // ── 5. Start mini mixed ──
    console.log('\nPhase 3: Attempt Flow');

    // Use page.evaluate to call the app's own fetchPost
    const startResult = await page.evaluate(async () => {
      try {
        const res = await window.fetchPost('/tg/mini/start', { mode: 'mixed' });
        const text = await res.text();
        return { status: res.status, url: res.url, redirected: res.redirected, body: text.substring(0, 500) };
      } catch (e) {
        return { error: e.message };
      }
    });

    const startChecks = [`Response status: ${startResult?.status ?? 'N/A'}`];

    if (startResult?.status === 200 || startResult?.redirected) {
      // The response might contain the test page HTML or redirect info
      // Try to extract attempt ID from response or navigate
      let redirectUrl = startResult.url || '';
      if (!redirectUrl.includes('/tg/test/')) {
        // Parse redirect from body
        const match = startResult.body?.match(/\/tg\/test\/(\d+)/);
        if (match) redirectUrl = `/tg/test/${match[1]}`;
      }

      if (redirectUrl.includes('/tg/test/')) {
        const fullUrl = redirectUrl.startsWith('http') ? redirectUrl : `${BASE}${redirectUrl}`;
        await page.goto(fullUrl, { waitUntil: 'networkidle' });
      }
    } else if (startResult?.status === 302 || startResult?.status === 301) {
      startChecks.push('Got redirect');
    }

    // Also try direct: check if we ended up on a test page
    let testUrl = page.url();
    let isOnTestPage = testUrl.includes('/tg/test/');

    if (!isOnTestPage) {
      // Try clicking the button directly
      await page.goto(`${BASE}/tg/mini`, { waitUntil: 'networkidle' });
      const btn = page.locator('button').filter({ hasText: /Смешанн/ }).first();
      if (await btn.count() > 0) {
        await btn.click();
        await page.waitForURL('**/tg/test/**', { timeout: 10000 }).catch(() => {});
        await page.waitForLoadState('networkidle').catch(() => {});
        testUrl = page.url();
        isOnTestPage = testUrl.includes('/tg/test/');
        startChecks.push(`Button click approach: ${isOnTestPage ? 'OK' : 'failed'}`);
      }
    }

    if (!isOnTestPage) {
      // Last resort: the fetchPost returned HTML with the page content
      // Try extracting redirect from the JSON response
      try {
        const json = JSON.parse(startResult.body);
        if (json.redirect) {
          await page.goto(`${BASE}${json.redirect}`, { waitUntil: 'networkidle' });
          testUrl = page.url();
          isOnTestPage = testUrl.includes('/tg/test/');
          startChecks.push(`JSON redirect: ${json.redirect}`);
        }
      } catch (e) { /* not JSON */ }
    }

    img = await screenshot(page, 'test-page');
    startChecks.unshift(isOnTestPage ? `On test page: ${testUrl}` : `Not on test page: ${testUrl}`);

    if (isOnTestPage) {
      attemptId = testUrl.match(/\/test\/(\d+)/)?.[1];
      startChecks.push(`Attempt ID: ${attemptId}`);

      const pageContent = await page.content();
      startChecks.push(pageContent.includes('display_task_number') ? 'display_task_number present' : 'No display_task_number in page');
      startChecks.push(pageContent.includes('attempt_task_number') ? 'attempt_task_number present' : 'No attempt_task_number in page');
    }
    addResult('Start Mini Mixed', testUrl, isOnTestPage ? 'OK' : 'FAIL', img, startChecks);

    // ── 6. Status API ──
    if (attemptId) {
      const statusResp = await page.request.get(`${BASE}/api/oge/attempts/${attemptId}/status`);
      let statusData = null;
      const statusChecks = [`HTTP ${statusResp.status()}`];
      if (statusResp.ok()) {
        statusData = await statusResp.json();
        statusChecks.push(`tasks_total: ${statusData?.summary?.tasks_total}`);
        statusChecks.push(`answered: ${statusData?.summary?.answered_count}`);
        statusChecks.push(`task numbers: ${(statusData?.tasks || []).map(t => t.task_number).join(', ')}`);
      }
      img = await screenshot(page, 'status-api');
      addResult('Status API', `GET /api/oge/attempts/${attemptId}/status`, statusResp.ok() ? 'OK' : 'FAIL', img, statusChecks);

      // ── 7. Commit answers ──
      // API routes are web routes needing CSRF — use page.evaluate to leverage session cookies
      const tasksTotal = statusData?.summary?.tasks_total ?? 0;
      const commitResults = await page.evaluate(async ({ attemptId, tasksTotal }) => {
        const results = [];
        for (let tn = 1; tn <= tasksTotal; tn++) {
          const answer = String(tn * 11);
          try {
            const res = await fetch(`/api/oge/attempts/${attemptId}/tasks/${tn}/commit`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''), 'Accept': 'application/json' },
              body: JSON.stringify({ answer }),
            });
            results.push({ tn, status: res.status, answer });
          } catch (e) {
            results.push({ tn, status: 0, answer, error: e.message });
          }
        }
        return results;
      }, { attemptId, tasksTotal });

      const commitChecks = commitResults.map(r => `Task ${r.tn}: HTTP ${r.status} (answer="${r.answer}")${r.error ? ' ERR: ' + r.error : ''}`);
      const allCommitsOk = commitResults.every(r => r.status === 200);
      img = await screenshot(page, 'after-commits');
      addResult('Commit Answers', `POST .../tasks/N/commit`, allCommitsOk ? 'OK' : (tasksTotal > 0 ? 'FAIL' : 'SKIP'), img, commitChecks);

      // ── 8. Submit ──
      const submitResult = await page.evaluate(async (attemptId) => {
        try {
          const res = await fetch(`/api/oge/attempts/${attemptId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''), 'Accept': 'application/json' },
            body: JSON.stringify({}),
          });
          const data = await res.json().catch(() => null);
          return { status: res.status, data };
        } catch (e) {
          return { status: 0, error: e.message };
        }
      }, attemptId);

      const submitChecks = [`HTTP ${submitResult.status}`];
      if (submitResult.data) submitChecks.push(`success: ${submitResult.data?.success}`);
      if (submitResult.error) submitChecks.push(`Error: ${submitResult.error}`);
      img = await screenshot(page, 'after-submit');
      addResult('Submit Attempt', `POST .../submit`, submitResult.status === 200 ? 'OK' : 'FAIL', img, submitChecks);

      // ── 9. Results page ──
      await page.goto(`${BASE}/tg/results/${attemptId}`, { waitUntil: 'networkidle' });
      img = await screenshot(page, 'results-page');
      const resultsHtml = await page.content();
      addResult('Results Page', page.url(), 'OK', img, [
        await page.locator('text=/\\d+\\/\\d+/').count() > 0 ? 'Score N/M displayed' : 'MISSING: score display',
        resultsHtml.includes('correct') || resultsHtml.includes('Правильн') || resultsHtml.includes('Верн') ? 'Correctness info found' : 'No correctness indicators',
      ]);

      // ── 10. History detail ──
      await page.goto(`${BASE}/tg/history/${attemptId}`, { waitUntil: 'networkidle' });
      img = await screenshot(page, 'history-detail');
      const detailHtml = await page.content();
      addResult('History Detail', page.url(), 'OK', img, [
        await page.locator('text=/\\d+\\/\\d+/').count() > 0 ? 'Score displayed' : 'MISSING: score',
        detailHtml.includes('Задание') || detailHtml.includes('task-text') || detailHtml.length > 5000 ? 'Task content present' : 'MISSING: task content',
        detailHtml.includes('Ошибк') || detailHtml.includes('wrong') || detailHtml.includes('incorrect') || detailHtml.includes('error-card') ? 'Error details shown' : 'No error details visible (may be all correct)',
      ]);
    }

    // ── 11. History list ──
    console.log('\nPhase 4: Other Pages');
    await page.goto(`${BASE}/tg/history`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'history-list');
    const historyCount = await page.locator('a[href*="history/"]').count();
    addResult('History List', page.url(), 'OK', img, [
      `${historyCount} history entries found`,
      historyCount > 0 ? 'Has at least one completed variant' : 'WARN: no history items',
    ]);

    // ── 12. Profile ──
    await page.goto(`${BASE}/tg/profile`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'profile');
    addResult('Profile', page.url(), 'OK', img, [
      (await page.content()).includes('QA Student') ? 'Name "QA Student" shown' : 'Student name not found',
    ]);

    // ── 13. Tasks Part 1 ──
    await page.goto(`${BASE}/tg/tasks-part1`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'tasks-part1');
    addResult('Tasks Part 1', page.url(), page.url().includes('login') ? 'FAIL' : 'OK', img, ['Page loaded']);

    // ── 14. Part 2 ──
    await page.goto(`${BASE}/tg/part2`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'part2');
    addResult('Part 2', page.url(), page.url().includes('login') ? 'FAIL' : 'OK', img, ['Page loaded']);

    // ── 15. Homework ──
    await page.goto(`${BASE}/tg/homework`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'homework');
    addResult('Homework', page.url(), page.url().includes('login') ? 'FAIL' : 'OK', img, ['Page loaded']);

    // ── 16. Tutor ──
    await page.goto(`${BASE}/tg/tutor`, { waitUntil: 'networkidle' });
    img = await screenshot(page, 'tutor');
    addResult('Tutor', page.url(), page.url().includes('login') ? 'FAIL' : 'OK', img, ['Page loaded']);

  } catch (err) {
    console.error('\nCRASH:', err.message);
    const img = await screenshot(page, 'crash');
    addResult('CRASH', page.url(), 'FAIL', img, [err.message], err.stack);
  }

  await browser.close();

  // ── Generate HTML Report ──
  const passed = results.filter(r => r.status === 'OK').length;
  const failed = results.filter(r => r.status === 'FAIL').length;
  const warned = results.filter(r => r.status === 'WARN' || r.status === 'SKIP').length;

  const html = `<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QA Report — Mini App Student</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0d0d0d;color:#e0e0e0}
.hdr{background:#1a1a2e;padding:20px 24px;border-bottom:1px solid #333}
.hdr h1{font-size:20px;margin-bottom:6px}
.hdr .meta{font-size:12px;color:#777}
.summary{display:flex;gap:12px;padding:14px 24px;background:#111;border-bottom:1px solid #222}
.badge{padding:5px 12px;border-radius:16px;font-size:12px;font-weight:600}
.b-ok{background:#062e06;color:#4ade80}.b-fail{background:#2e0606;color:#f87171}.b-warn{background:#2e2e06;color:#fbbf24}.b-total{background:#1a1a2e;color:#93c5fd}
nav{position:sticky;top:0;background:#111;border-bottom:1px solid #222;padding:8px 16px;z-index:10;overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch}
nav a{display:inline-block;padding:5px 10px;margin:2px;border-radius:6px;font-size:11px;color:#999;text-decoration:none;border-left:3px solid transparent}
nav a:hover{background:#1a1a1a}
nav a.ok{border-left-color:#4ade80}nav a.fail{border-left-color:#f87171}nav a.warn,nav a.skip{border-left-color:#fbbf24}
.step{padding:20px 24px;border-bottom:1px solid #161616}
.step:target{background:#1a1a2e}
.sh{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.sn{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.sn.ok{background:#062e06;color:#4ade80}.sn.fail{background:#2e0606;color:#f87171}.sn.warn,.sn.skip{background:#2e2e06;color:#fbbf24}
.st{font-size:14px;font-weight:600}
.su{font-size:10px;color:#555;margin-top:1px;word-break:break-all}
.checks{margin:8px 0;padding-left:18px;list-style:none}
.checks li{font-size:12px;margin-bottom:2px;color:#aaa;position:relative;padding-left:12px}
.checks li::before{content:"·";position:absolute;left:0;color:#555}
.miss{color:#f87171;font-weight:600}
.si{margin-top:10px;max-width:390px;border-radius:10px;border:1px solid #2a2a2a;cursor:pointer;transition:border-color .2s}
.si:hover{border-color:#555}
.ts{font-size:9px;color:#444;margin-top:6px}
.lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:100;justify-content:center;align-items:center;cursor:zoom-out}
.lb.on{display:flex}
.lb img{max-width:95vw;max-height:95vh;border-radius:8px}
</style>
</head>
<body>
<div class="hdr">
<h1>QA Report: Mini App — Student Flow</h1>
<div class="meta">${new Date().toISOString().replace('T',' ').slice(0,19)} UTC · ${BASE} · ${EMAIL}</div>
</div>
<div class="summary">
<span class="badge b-total">${results.length} steps</span>
<span class="badge b-ok">${passed} OK</span>
${failed?`<span class="badge b-fail">${failed} FAIL</span>`:''}
${warned?`<span class="badge b-warn">${warned} WARN</span>`:''}
</div>
<nav>${results.map((r,i)=>`<a href="#s${i+1}" class="${r.status.toLowerCase()}">${i+1}. ${r.step}</a>`).join('')}</nav>
${results.map((r,i)=>`<div class="step" id="s${i+1}">
<div class="sh"><div class="sn ${r.status.toLowerCase()}">${i+1}</div><div><div class="st">${r.step}</div><div class="su">${r.url}</div></div></div>
<ul class="checks">${r.checks.map(c=>`<li>${c.startsWith('MISSING')?`<span class="miss">${c}</span>`:c}</li>`).join('')}</ul>
${r.error?`<pre style="color:#f87171;font-size:10px;margin-top:6px;white-space:pre-wrap">${r.error}</pre>`:''}
<img class="si" src="${r.img}" alt="${r.step}" onclick="lbox(this.src)" loading="lazy">
<div class="ts">${r.ts}</div>
</div>`).join('')}
<div id="lb" class="lb" onclick="this.classList.remove('on')"><img id="lbi" src=""></div>
<script>function lbox(s){document.getElementById('lbi').src=s;document.getElementById('lb').classList.add('on')}</script>
</body></html>`;

  fs.writeFileSync(path.join(REPORT_DIR, 'index.html'), html);
  console.log(`\n════════════════════════════════`);
  console.log(`Report: ${REPORT_DIR}/index.html`);
  console.log(`Results: ${passed} OK, ${failed} FAIL, ${warned} WARN out of ${results.length}`);
})();
