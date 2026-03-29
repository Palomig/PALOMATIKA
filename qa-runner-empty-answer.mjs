/**
 * QA: Empty answer must count as incorrect
 *
 * Scenario:
 *  - Start a mini OGE (Algebra mode)
 *  - Answer all tasks EXCEPT the last one (leave it blank)
 *  - Submit
 *  - Verify the score shows X/(total) NOT X/answered
 *    e.g. if 5 tasks and 4 answered → should show X/5, never X/4
 */
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
  for (const c of checks) {
    const prefix = c.startsWith('FAIL') || c.startsWith('MISSING') || c.startsWith('BUG') ? '\x1b[31m' : '\x1b[90m';
    console.log(`      ${prefix}→ ${c}\x1b[0m`);
  }
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
  let tasksTotal = 0;

  try {
    // ── 1. Login ──
    console.log('\n═══ Phase 1: Login ═══');
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const loggedInUrl = page.url();
    const loginOk = !loggedInUrl.includes('/login');
    const img1 = await screenshot(page, 'login');
    addResult('Login', loggedInUrl, loginOk ? 'OK' : 'FAIL', img1, [
      loginOk ? `Logged in: ${loggedInUrl}` : 'FAIL: still on login page',
    ]);
    if (!loginOk) throw new Error('Login failed');

    // ── 2. Start mini OGE (algebra) ──
    console.log('\n═══ Phase 2: Start Mini OGE ═══');
    await page.goto(`${BASE}/tg/mini`, { waitUntil: 'networkidle' });
    const img2 = await screenshot(page, 'mini-selection');
    addResult('Mini Selection Page', page.url(), 'OK', img2, ['Page loaded']);

    // Call the start API directly via fetchPost (same as Alpine.js does)
    const startData = await page.evaluate(async () => {
      try {
        const res = await window.fetchPost('/tg/mini/start', { mode: 'algebra' });
        const data = await res.json();
        return { ok: res.ok, status: res.status, data };
      } catch (e) {
        return { ok: false, error: e.message };
      }
    });

    const redirectUrl = startData?.data?.redirect;
    if (redirectUrl) {
      await page.goto(`${BASE}${redirectUrl}`, { waitUntil: 'networkidle' });
    }

    const testUrl = page.url();
    const onTestPage = testUrl.includes('/tg/test/');
    attemptId = testUrl.match(/\/test\/(\d+)/)?.[1];
    const img3 = await screenshot(page, 'test-start');
    addResult('Start Mini OGE (algebra)', testUrl, onTestPage ? 'OK' : 'FAIL', img3, [
      onTestPage ? `Test page: ${testUrl}` : `FAIL: not on test page: ${testUrl}`,
      `Attempt ID: ${attemptId ?? 'not found'}`,
      `API status: ${startData?.status ?? 'N/A'}`,
      redirectUrl ? `Redirect: ${redirectUrl}` : `FAIL: no redirect in response — ${JSON.stringify(startData?.data)}`,
    ]);
    if (!onTestPage || !attemptId) throw new Error('Could not start mini OGE');

    // ── 3. Check how many tasks ──
    console.log('\n═══ Phase 3: Get task count via Status API ═══');
    const statusResp = await page.request.get(`${BASE}/api/oge/attempts/${attemptId}/status`);
    let statusData = null;
    if (statusResp.ok()) {
      statusData = await statusResp.json();
    }
    tasksTotal = statusData?.summary?.tasks_total ?? 0;
    const img4 = await screenshot(page, 'status-api');
    addResult('Status API', `/api/oge/attempts/${attemptId}/status`, statusResp.ok() ? 'OK' : 'FAIL', img4, [
      `tasks_total: ${tasksTotal}`,
      `HTTP: ${statusResp.status()}`,
      tasksTotal > 1 ? 'Has multiple tasks — can skip one' : 'WARN: only 0–1 tasks found',
    ]);
    if (tasksTotal < 2) throw new Error(`Need at least 2 tasks, got ${tasksTotal}`);

    // ── 4. Answer all tasks EXCEPT the last one ──
    console.log(`\n═══ Phase 4: Answer tasks 1..${tasksTotal - 1} (skip task ${tasksTotal}) ═══`);
    const SKIP_TASK = tasksTotal; // leave the last task empty

    const commitResults = await page.evaluate(async ({ attemptId, tasksTotal, skipTask }) => {
      const results = [];
      const token = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');
      for (let tn = 1; tn <= tasksTotal; tn++) {
        if (tn === skipTask) {
          results.push({ tn, status: 'SKIPPED', answer: '' });
          continue;
        }
        const answer = String(tn); // simple numeric answers
        try {
          const res = await fetch(`/api/oge/attempts/${attemptId}/tasks/${tn}/commit`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-XSRF-TOKEN': token,
              'Accept': 'application/json',
            },
            body: JSON.stringify({ answer }),
          });
          results.push({ tn, status: res.status, answer });
        } catch (e) {
          results.push({ tn, status: 0, answer, error: e.message });
        }
      }
      return results;
    }, { attemptId, tasksTotal, skipTask: SKIP_TASK });

    const commitChecks = commitResults.map(r =>
      r.status === 'SKIPPED'
        ? `Task ${r.tn}: ⬜ INTENTIONALLY SKIPPED (empty)`
        : `Task ${r.tn}: HTTP ${r.status} answer="${r.answer}"${r.error ? ' ERR:' + r.error : ''}`
    );
    const answeredCount = commitResults.filter(r => r.status === 200).length;
    const img5 = await screenshot(page, 'after-commits');
    addResult('Commit Answers (skip last)', 'POST .../tasks/N/commit', 'OK', img5, [
      `Answered: ${answeredCount}/${tasksTotal} (task ${SKIP_TASK} left empty)`,
      ...commitChecks,
    ]);

    // ── 5. Submit ──
    console.log('\n═══ Phase 5: Submit ═══');
    const submitResult = await page.evaluate(async ({ attemptId, answers }) => {
      const token = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');
      try {
        const res = await fetch(`/api/oge/attempts/${attemptId}/submit`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': token,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ answers }),
        });
        const data = await res.json().catch(() => null);
        return { status: res.status, data };
      } catch (e) {
        return { status: 0, error: e.message };
      }
    }, {
      attemptId,
      // Send only answers for tasks 1..N-1, leave N out (empty)
      answers: Object.fromEntries(
        commitResults
          .filter(r => r.status === 200)
          .map(r => [r.tn, r.answer])
      ),
    });

    const img6 = await screenshot(page, 'after-submit');
    addResult('Submit Attempt', `POST .../submit`, submitResult.status === 200 ? 'OK' : 'FAIL', img6, [
      `HTTP: ${submitResult.status}`,
      `success: ${submitResult.data?.success ?? 'N/A'}`,
      submitResult.error ? `Error: ${submitResult.error}` : 'No error',
    ]);

    // ── 6. Results page — THE KEY CHECK ──
    console.log('\n═══ Phase 6: Results — checking score totals ═══');
    await page.goto(`${BASE}/tg/results/${attemptId}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    const img7 = await screenshot(page, 'results-page');

    const pageText = await page.innerText('body').catch(() => '');
    const pageHtml = await page.content();

    // Extract X/Y scores from the page
    const scoreMatches = [...pageText.matchAll(/(\d+)\s*\/\s*(\d+)/g)];
    const scores = scoreMatches.map(m => ({ correct: parseInt(m[1]), total: parseInt(m[2]) }));

    const resultsChecks = [];

    if (scores.length === 0) {
      resultsChecks.push('FAIL: No X/Y score found on results page');
    } else {
      resultsChecks.push(`Scores found: ${scores.map(s => `${s.correct}/${s.total}`).join(', ')}`);
    }

    // The critical check: total shown must equal tasksTotal, not answeredCount
    const mainScore = scores[0];
    if (mainScore) {
      if (mainScore.total === tasksTotal) {
        resultsChecks.push(`OK: total=${mainScore.total} matches tasks_total=${tasksTotal} ✓`);
      } else if (mainScore.total === answeredCount) {
        resultsChecks.push(`BUG DETECTED: total=${mainScore.total} = answered_count=${answeredCount} — skipped task NOT counted as error!`);
        resultsChecks.push(`Expected total to be ${tasksTotal} (all tasks including skipped)`);
      } else {
        resultsChecks.push(`WARN: total=${mainScore.total}, tasks_total=${tasksTotal}, answered=${answeredCount} — unexpected value`);
      }

      // The score "big" number (displayed prominently)
      const scoreBigEl = page.locator('.score-big').first();
      if (await scoreBigEl.count() > 0) {
        const scoreBigText = await scoreBigEl.innerText();
        resultsChecks.push(`Score display (.score-big): "${scoreBigText}"`);
      }
    }

    // Check that there's an "unanswered" or "incorrect" entry for the skipped task
    const hasUnansweredOrSkip = pageHtml.includes('skip') || pageHtml.includes('unanswered') ||
      pageHtml.includes('Пропущ') || pageHtml.includes('Не отвеч') || pageHtml.includes('пропущ');
    resultsChecks.push(hasUnansweredOrSkip ? 'Skipped/unanswered indicator present' : 'No skip indicator (may show as wrong)');

    const resultStatus = mainScore?.total === tasksTotal ? 'OK' :
                         mainScore?.total === answeredCount ? 'FAIL' : 'WARN';
    addResult('Results Score Check', page.url(), resultStatus, img7, resultsChecks);

    // ── 7. History list — check score there too ──
    console.log('\n═══ Phase 7: History list score ═══');
    await page.goto(`${BASE}/tg/history`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const img8 = await screenshot(page, 'history-list');

    const historyText = await page.innerText('body').catch(() => '');
    const historyScores = [...historyText.matchAll(/(\d+)\s*\/\s*(\d+)/g)].map(m => ({
      correct: parseInt(m[1]), total: parseInt(m[2])
    }));

    const historyChecks = [];
    if (historyScores.length === 0) {
      historyChecks.push('WARN: No score found in history list');
    } else {
      historyChecks.push(`Scores in history: ${historyScores.map(s => `${s.correct}/${s.total}`).join(', ')}`);
      const latestHistoryScore = historyScores[0];
      if (latestHistoryScore.total === tasksTotal) {
        historyChecks.push(`OK: history total=${latestHistoryScore.total} = tasks_total=${tasksTotal} ✓`);
      } else if (latestHistoryScore.total === answeredCount) {
        historyChecks.push(`BUG: history shows ${latestHistoryScore.correct}/${latestHistoryScore.total} — skipped task excluded from total!`);
      } else {
        historyChecks.push(`WARN: history total=${latestHistoryScore.total}, expected ${tasksTotal}`);
      }
    }

    const historyStatus = historyScores[0]?.total === tasksTotal ? 'OK' :
                          historyScores[0]?.total === answeredCount ? 'FAIL' : 'WARN';
    addResult('History List Score Check', page.url(), historyStatus, img8, historyChecks);

    // ── 8. History detail ──
    console.log('\n═══ Phase 8: History detail ═══');
    await page.goto(`${BASE}/tg/history/${attemptId}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const img9 = await screenshot(page, 'history-detail');

    const detailText = await page.innerText('body').catch(() => '');
    const detailScores = [...detailText.matchAll(/(\d+)\s*\/\s*(\d+)/g)].map(m => ({
      correct: parseInt(m[1]), total: parseInt(m[2])
    }));
    const detailChecks = [];
    if (detailScores.length > 0) {
      const ds = detailScores[0];
      detailChecks.push(`Score: ${ds.correct}/${ds.total}`);
      if (ds.total === tasksTotal) {
        detailChecks.push(`OK: total=${ds.total} = tasks_total=${tasksTotal} ✓`);
      } else if (ds.total === answeredCount) {
        detailChecks.push(`BUG: total=${ds.total} = answered=${answeredCount} — skip not counted`);
      }
    } else {
      detailChecks.push('WARN: no score found in history detail');
    }

    const detailStatus = detailScores[0]?.total === tasksTotal ? 'OK' :
                         detailScores[0]?.total === answeredCount ? 'FAIL' : 'WARN';
    addResult('History Detail Score Check', page.url(), detailStatus, img9, detailChecks);

  } catch (err) {
    console.error('\nCRASH:', err.message);
    const img = await screenshot(page, 'crash').catch(() => '');
    addResult('CRASH', page.url(), 'FAIL', img, [err.message], err.stack);
  }

  await browser.close();

  // ── Summary ──
  const passed = results.filter(r => r.status === 'OK').length;
  const failed = results.filter(r => r.status === 'FAIL').length;
  const warned = results.filter(r => r.status === 'WARN' || r.status === 'SKIP').length;

  console.log(`\n${'═'.repeat(50)}`);
  console.log(`QA RESULT: ${passed} OK · ${failed} FAIL · ${warned} WARN`);
  if (failed === 0) {
    console.log('\x1b[32m✓ Empty-answer-as-error test PASSED\x1b[0m');
  } else {
    console.log('\x1b[31m✗ FAILURES DETECTED — empty answers may not count as errors\x1b[0m');
  }
  console.log(`${'═'.repeat(50)}\n`);

  // ── HTML Report ──
  const html = `<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QA: Empty Answer = Error</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0d0d0d;color:#e0e0e0}
.hdr{background:#1a1a2e;padding:20px 24px;border-bottom:1px solid #333}
.hdr h1{font-size:20px;margin-bottom:6px}
.hdr .meta{font-size:12px;color:#777}
.hdr .scenario{font-size:13px;color:#93c5fd;margin-top:8px;line-height:1.5}
.summary{display:flex;gap:12px;padding:14px 24px;background:#111;border-bottom:1px solid #222}
.badge{padding:5px 12px;border-radius:16px;font-size:12px;font-weight:600}
.b-ok{background:#062e06;color:#4ade80}.b-fail{background:#2e0606;color:#f87171}.b-warn{background:#2e2e06;color:#fbbf24}.b-total{background:#1a1a2e;color:#93c5fd}
nav{position:sticky;top:0;background:#111;border-bottom:1px solid #222;padding:8px 16px;z-index:10;overflow-x:auto;white-space:nowrap}
nav a{display:inline-block;padding:5px 10px;margin:2px;border-radius:6px;font-size:11px;color:#999;text-decoration:none;border-left:3px solid transparent}
nav a.ok{border-left-color:#4ade80}nav a.fail{border-left-color:#f87171}nav a.warn{border-left-color:#fbbf24}
.step{padding:20px 24px;border-bottom:1px solid #161616}
.step:target{background:#1a1a2e}
.sh{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.sn{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.sn.ok{background:#062e06;color:#4ade80}.sn.fail{background:#2e0606;color:#f87171}.sn.warn{background:#2e2e06;color:#fbbf24}
.st{font-size:14px;font-weight:600}
.su{font-size:10px;color:#555;margin-top:1px;word-break:break-all}
.checks{margin:8px 0;padding-left:18px;list-style:none}
.checks li{font-size:12px;margin-bottom:2px;color:#aaa;position:relative;padding-left:12px}
.checks li::before{content:"·";position:absolute;left:0;color:#555}
.miss{color:#f87171;font-weight:700}
.ok-txt{color:#4ade80;font-weight:700}
.si{margin-top:10px;max-width:390px;border-radius:10px;border:1px solid #2a2a2a;cursor:pointer}
.si:hover{border-color:#555}
.ts{font-size:9px;color:#444;margin-top:6px}
.lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:100;justify-content:center;align-items:center;cursor:zoom-out}
.lb.on{display:flex}
.lb img{max-width:95vw;max-height:95vh;border-radius:8px}
</style>
</head>
<body>
<div class="hdr">
<h1>QA: Пустой ответ = ошибка</h1>
<div class="meta">${new Date().toISOString().replace('T',' ').slice(0,19)} UTC · ${BASE}</div>
<div class="scenario">
  Сценарий: запустить мини-ОГЭ, ответить на все задания кроме последнего,<br>
  отправить → результат должен показывать X/<b>${tasksTotal || '?'}</b> (а не X/${tasksTotal > 0 ? tasksTotal - 1 : '?-1'}).
</div>
</div>
<div class="summary">
<span class="badge b-total">${results.length} steps</span>
<span class="badge b-ok">${passed} OK</span>
${failed ? `<span class="badge b-fail">${failed} FAIL</span>` : ''}
${warned ? `<span class="badge b-warn">${warned} WARN</span>` : ''}
</div>
<nav>${results.map((r, i) => `<a href="#s${i+1}" class="${r.status.toLowerCase()}">${i+1}. ${r.step}</a>`).join('')}</nav>
${results.map((r, i) => `<div class="step" id="s${i+1}">
<div class="sh"><div class="sn ${r.status.toLowerCase()}">${i+1}</div><div><div class="st">${r.step}</div><div class="su">${r.url}</div></div></div>
<ul class="checks">${r.checks.map(c => `<li>${
  c.startsWith('BUG') || c.startsWith('FAIL') || c.startsWith('MISSING') ? `<span class="miss">${c}</span>` :
  c.startsWith('OK:') ? `<span class="ok-txt">${c}</span>` : c
}</li>`).join('')}</ul>
${r.error ? `<pre style="color:#f87171;font-size:10px;margin-top:6px;white-space:pre-wrap">${r.error}</pre>` : ''}
${r.img ? `<img class="si" src="${r.img}" alt="${r.step}" onclick="lbox(this.src)" loading="lazy">` : ''}
<div class="ts">${r.ts}</div>
</div>`).join('')}
<div id="lb" class="lb" onclick="this.classList.remove('on')"><img id="lbi" src=""></div>
<script>function lbox(s){document.getElementById('lbi').src=s;document.getElementById('lb').classList.add('on')}</script>
</body>
</html>`;

  const reportPath = path.join(REPORT_DIR, 'empty-answer-qa.html');
  fs.writeFileSync(reportPath, html);
  console.log(`Report: http://193.23.3.134/qa-reports/empty-answer-qa.html`);
})();
