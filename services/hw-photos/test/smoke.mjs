/**
 * Smoke-проверка hw-photos: node test/smoke.mjs [base_url]
 * Секрет читается из того же EnvironmentFile, что у systemd-юнита.
 */
import crypto from 'node:crypto';
import fs from 'node:fs';
import assert from 'node:assert/strict';

const BASE = process.argv[2] || 'http://127.0.0.1:4320';
const env = fs.readFileSync('/home/dev/.agent-secrets/hw-photos.env', 'utf8');
const SECRET = /HW_PHOTOS_SECRET=(.+)/.exec(env)[1].trim();

const sign = data => crypto.createHmac('sha256', SECRET).update(data).digest('base64url');
const b64u = s => Buffer.from(s).toString('base64url');

function uploadToken(claim) {
  const body = b64u(JSON.stringify(claim));
  return `t.${body}.${sign(body)}`;
}

function readUrl(photoId, { ttl = 600, width = null, sig = null } = {}) {
  const exp = Math.floor(Date.now() / 1000) + ttl;
  const s = sig ?? sign(`${photoId}.${exp}`);
  const w = width ? `&w=${width}` : '';
  return `${BASE}/v1/photo/${encodeURIComponent(photoId)}?exp=${exp}&sig=${s}${w}`;
}

async function upload(bytes, token, filename = 'photo.jpg') {
  const form = new FormData();
  form.append('photo', new Blob([bytes]), filename);
  return fetch(`${BASE}/v1/photos`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    body: form,
  });
}

/**
 * Настоящий JPEG нужного размера — magic-байты сервис проверяет всерьёз.
 * Пиксели грузим одним блоком из os.urandom: списки на 9 млн кортежей съедают
 * под гигабайт, а на этой машине злой earlyoom.
 */
function jpeg(px) {
  const { execFileSync } = require('node:child_process');
  return execFileSync('python3', ['-c', `
import os, sys
from PIL import Image
px = ${px}
im = Image.frombytes('RGB', (px, px), os.urandom(px * px * 3))
im.save(sys.stdout.buffer, format='JPEG', quality=95)
`], { maxBuffer: 200 * 1024 * 1024 });
}

const require = (await import('node:module')).createRequire(import.meta.url);

// Лимит сервиса — 30 загрузок в час на связку assignment+task+student, поэтому
// каждый прогон берёт свою задачу: иначе повторные запуски начнут ловить 429.
const TASK = 100000 + Math.floor(Math.random() * 900000);
const claim = () => ({ a: 7, k: TASK, s: 35, e: Math.floor(Date.now() / 1000) + 600 });
let failures = 0;
const check = async (name, fn) => {
  try {
    await fn();
    console.log(`  ok   ${name}`);
  } catch (err) {
    failures++;
    console.log(`  FAIL ${name}: ${err.message}`);
  }
};

console.log(`hw-photos smoke @ ${BASE}`);

await check('healthz', async () => {
  const r = await fetch(`${BASE}/healthz`);
  assert.equal(r.status, 200);
  assert.equal((await r.json()).ok, true);
});

let photoId = null;

await check('загрузка фото по валидному токену', async () => {
  const r = await upload(jpeg(1200), uploadToken(claim()));
  assert.equal(r.status, 200);
  const body = await r.json();
  assert.match(body.photo_id, /^p\..+\..+$/);
  assert.ok(body.bytes > 1000);
  assert.ok(body.width > 0 && body.height > 0);
  photoId = body.photo_id;
});

await check('photo_id подписан и содержит assignment/task/student', async () => {
  const [, payload, sig] = photoId.split('.');
  assert.equal(sig, sign(payload));
  const meta = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
  assert.deepEqual([meta.a, meta.k, meta.s], [7, TASK, 35]);
});

await check('большой снимок ужимается до 2000px и в JPEG', async () => {
  const r = await upload(jpeg(3000), uploadToken(claim()));
  const body = await r.json();
  assert.equal(r.status, 200);
  assert.equal(Math.max(body.width, body.height), 2000);
  assert.equal(body.stored_as, 'jpg');
});

await check('чтение по подписанной ссылке', async () => {
  const r = await fetch(readUrl(photoId));
  assert.equal(r.status, 200);
  assert.equal(r.headers.get('content-type'), 'image/jpeg');
  assert.ok(Number(r.headers.get('content-length')) > 1000);
});

await check('миниатюра ?w=400 меньше оригинала', async () => {
  const full = await fetch(readUrl(photoId));
  const thumb = await fetch(readUrl(photoId, { width: 400 }));
  assert.equal(thumb.status, 200);
  assert.ok(Number(thumb.headers.get('content-length')) < Number(full.headers.get('content-length')));
});

await check('чужая подпись на чтение → 403', async () => {
  const r = await fetch(readUrl(photoId, { sig: 'deadbeef' }));
  assert.equal(r.status, 403);
});

await check('просроченная ссылка → 403', async () => {
  const r = await fetch(readUrl(photoId, { ttl: -60 }));
  assert.equal(r.status, 403);
});

await check('без токена → 401', async () => {
  const r = await upload(jpeg(200), '');
  assert.equal(r.status, 401);
});

await check('подделанный токен → 401', async () => {
  const bad = uploadToken(claim()).replace(/\.[^.]+$/, '.forged');
  const r = await upload(jpeg(200), bad);
  assert.equal(r.status, 401);
});

await check('просроченный токен → 401', async () => {
  const r = await upload(jpeg(200), uploadToken({ a: 7, k: 16, s: 35, e: Math.floor(Date.now() / 1000) - 10 }));
  assert.equal(r.status, 401);
});

await check('не картинка → 415', async () => {
  const r = await upload(Buffer.from('%PDF-1.7 not an image at all'), uploadToken(claim()), 'solution.pdf');
  assert.equal(r.status, 415);
});

await check('переименованный в .jpg PDF тоже 415', async () => {
  const r = await upload(Buffer.from('%PDF-1.7 still not an image'), uploadToken(claim()), 'solution.jpg');
  assert.equal(r.status, 415);
});

await check('файл больше лимита → 413', async () => {
  const big = Buffer.concat([Buffer.from([0xff, 0xd8, 0xff]), crypto.randomBytes(26 * 1024 * 1024)]);
  const r = await upload(big, uploadToken(claim()));
  assert.equal(r.status, 413);
});

await check('несуществующее фото → 404', async () => {
  const meta = { a: 7, k: TASK, s: 35, t: 1, r: 'deadbeef', x: 'jpg' };
  const body = Buffer.from(JSON.stringify(meta)).toString('base64url');
  const r = await fetch(readUrl(`p.${body}.${sign(body)}`));
  assert.equal(r.status, 404);
});

console.log(failures === 0 ? 'ВСЁ ЗЕЛЁНОЕ' : `ПАДЕНИЙ: ${failures}`);
process.exit(failures === 0 ? 0 : 1);
