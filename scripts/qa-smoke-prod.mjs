import fs from 'fs';
import path from 'path';

const REPORT_DIR = '/home/dev/palomatika/public/qa-reports';
const REPORT_PATH = path.join(REPORT_DIR, 'prod-smoke.html');
const JSON_PATH = path.join(REPORT_DIR, 'prod-smoke.json');

fs.mkdirSync(REPORT_DIR, { recursive: true });

const UA = 'Palomatika-QA-Smoke/1.0 (+internal)';

const checks = [
  { url: 'https://palomatika.ru/',            expect: { status: 200, contains: ['palomatika'] } },
  { url: 'https://palomatika.ru/login',       expect: { status: 200, contains: ['password', 'email'] } },
  { url: 'https://palomatika.ru/register',    expect: { status: 200, contains: ['palomatika'] } },
  { url: 'https://palomatika.ru/oge',         expect: { statusIn: [200, 301, 302], finalHostIn: ['student.palomatika.ru'] } },
  { url: 'https://palomatika.ru/robots.txt',  expect: { status: 200, contains: ['User-agent'] } },
  { url: 'https://palomatika.ru/forstas',     expect: { status: 200 } },
  { url: 'https://palomatika.ru/kanban',      expect: { status: 200 } },
  { url: 'https://palomatika.ru/roadmap',     expect: { status: 200 } },
  { url: 'https://palomatika.ru/healthz',     expect: { statusIn: [200, 404] }, optional: true },
  { url: 'https://student.palomatika.ru/',       expect: { status: 200 } },
  { url: 'https://student.palomatika.ru/login',  expect: { status: 200 } },
  { url: 'https://student.palomatika.ru/manifest.webmanifest', expect: { statusIn: [200, 404] }, optional: true },
  { url: 'https://student.palomatika.ru/manifest.json', expect: { statusIn: [200, 404] }, optional: true },
  { url: 'https://palomatika.ru/favicon.ico', expect: { status: 200 } },
  // Прод-specific проверки на внедрённый функционал
  { url: 'https://palomatika.ru/vpr',         expect: { statusIn: [200, 302, 404] }, optional: true },
  { url: 'https://palomatika.ru/ege',         expect: { statusIn: [200, 302, 404] }, optional: true },
  { url: 'https://palomatika.ru/practice',    expect: { statusIn: [200, 302, 404] }, optional: true },
];

function nowIso() { return new Date().toISOString(); }

async function runOne(entry) {
  const t0 = Date.now();
  let res, finalUrl, body = '', err = null;
  try {
    res = await fetch(entry.url, {
      method: 'GET',
      redirect: 'follow',
      headers: { 'User-Agent': UA, 'Accept': 'text/html,application/xhtml+xml,*/*' },
      signal: AbortSignal.timeout(15000),
    });
    finalUrl = res.url;
    body = await res.text();
  } catch (e) {
    err = e.message;
  }
  const ms = Date.now() - t0;

  const r = {
    url: entry.url,
    optional: !!entry.optional,
    status: res?.status ?? null,
    final_url: finalUrl ?? null,
    bytes: body.length,
    ms,
    error: err,
    pass: true,
    reasons: [],
  };

  if (err) {
    r.pass = false;
    r.reasons.push('fetch error: ' + err);
    return r;
  }

  const { expect } = entry;
  if (expect.status != null && res.status !== expect.status) {
    r.pass = false;
    r.reasons.push(`status ${res.status} != ${expect.status}`);
  }
  if (expect.statusIn && !expect.statusIn.includes(res.status)) {
    r.pass = false;
    r.reasons.push(`status ${res.status} not in [${expect.statusIn.join(', ')}]`);
  }
  if (expect.contains) {
    for (const m of expect.contains) {
      if (!body.toLowerCase().includes(m.toLowerCase())) {
        r.pass = false;
        r.reasons.push(`missing marker: "${m}"`);
      }
    }
  }
  if (expect.finalHostIn) {
    try {
      const h = new URL(finalUrl).hostname;
      if (!expect.finalHostIn.includes(h)) {
        r.pass = false;
        r.reasons.push(`final host ${h} not in [${expect.finalHostIn.join(', ')}]`);
      }
    } catch {}
  }
  if (/<title[^>]*>\s*(whoops|something went wrong|server error|500)\b/i.test(body)) {
    r.pass = false;
    r.reasons.push('prod error page signature in body');
  }
  if (/Whoops, looks like something went wrong/i.test(body)) {
    r.pass = false;
    r.reasons.push('Laravel Whoops page detected');
  }

  return r;
}

console.log(`Prod smoke — ${nowIso()}`);
const results = [];
for (const e of checks) {
  process.stdout.write(`→ ${e.url} ... `);
  const r = await runOne(e);
  results.push(r);
  if (r.pass) console.log(`OK ${r.status} ${r.bytes}B ${r.ms}ms`);
  else        console.log(`FAIL ${r.status ?? '-'} — ${r.reasons.join('; ')}`);
}

const total = results.length;
const failed = results.filter(r => !r.pass && !r.optional);
const warn   = results.filter(r => !r.pass && r.optional);
const ok     = results.filter(r => r.pass);

const summary = {
  ts: nowIso(),
  total,
  ok: ok.length,
  failed: failed.length,
  warn: warn.length,
  results,
};

fs.writeFileSync(JSON_PATH, JSON.stringify(summary, null, 2));

function esc(s) { return String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function rowClass(r) { return r.pass ? 'ok' : (r.optional ? 'warn' : 'fail'); }

const html = `<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8">
<title>Palomatika — Prod Smoke — ${esc(summary.ts)}</title>
<style>
  body{font:14px/1.5 ui-monospace,SFMono-Regular,Consolas,monospace;background:#0b1020;color:#e6e9f2;padding:20px;max-width:1100px;margin:auto}
  h1{color:#f59e0b;margin:0 0 4px}
  .meta{color:#8b93a8;margin-bottom:20px}
  .counts{display:flex;gap:14px;margin:12px 0 24px}
  .chip{padding:6px 12px;border-radius:8px;font-weight:600}
  .chip.ok{background:#052e1e;color:#34d399}
  .chip.fail{background:#3b0b13;color:#f87171}
  .chip.warn{background:#3d2e06;color:#fbbf24}
  table{width:100%;border-collapse:collapse;background:#0f1630;border-radius:10px;overflow:hidden}
  th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #1f2947;vertical-align:top}
  th{background:#121a37;color:#a9b3cc;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.06em}
  tr.ok td{background:transparent}
  tr.fail td{background:#25080e}
  tr.warn td{background:#1f1805}
  td.status{font-weight:700}
  .ok td.status{color:#34d399}
  .fail td.status{color:#f87171}
  .warn td.status{color:#fbbf24}
  .url{word-break:break-all;color:#93c5fd}
  .reasons{color:#f87171}
  .mini{color:#8b93a8;font-size:12px}
</style></head><body>
<h1>Palomatika — Prod Smoke</h1>
<div class="meta">${esc(summary.ts)} · ${total} checks</div>
<div class="counts">
  <div class="chip ok">✓ OK: ${ok.length}</div>
  <div class="chip fail">✗ FAIL: ${failed.length}</div>
  <div class="chip warn">~ WARN (optional): ${warn.length}</div>
</div>
<table>
<thead><tr><th>#</th><th>URL</th><th>Status</th><th>Bytes</th><th>ms</th><th>Final URL / Notes</th></tr></thead>
<tbody>
${results.map((r, i) => `<tr class="${rowClass(r)}">
  <td>${i+1}</td>
  <td class="url">${esc(r.url)}${r.optional ? ' <span class="mini">(optional)</span>' : ''}</td>
  <td class="status">${esc(r.status ?? '-')}</td>
  <td>${r.bytes}</td>
  <td>${r.ms}</td>
  <td>
    ${r.final_url && r.final_url !== r.url ? `<div class="mini">→ ${esc(r.final_url)}</div>` : ''}
    ${r.error ? `<div class="reasons">error: ${esc(r.error)}</div>` : ''}
    ${r.reasons.length ? `<div class="reasons">${r.reasons.map(esc).join('<br>')}</div>` : ''}
  </td>
</tr>`).join('\n')}
</tbody></table>
<div class="meta" style="margin-top:20px">
  Отчёт сгенерирован ${esc(summary.ts)} · JSON: <a href="prod-smoke.json" style="color:#93c5fd">prod-smoke.json</a>
</div>
</body></html>`;

fs.writeFileSync(REPORT_PATH, html);

console.log('');
console.log(`Summary: ${ok.length} OK, ${failed.length} FAIL, ${warn.length} WARN (optional)`);
console.log(`Report : ${REPORT_PATH}`);
console.log(`JSON   : ${JSON_PATH}`);

process.exit(failed.length === 0 ? 0 : 1);
