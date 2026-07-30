'use strict';

/**
 * hw-photos — хранилище фото решений домашки PALOMATIKA.
 *
 * Зачем отдельный сервис: прод Laravel живёт на шаред-хостинге Timeweb, где
 * public/storage — не симлинк (по /storage/... всегда 404), а канал и диск
 * дорогие. Фото ученики льют напрямую сюда, минуя Timeweb.
 *
 * Доверие держится на одном общем секрете с Laravel (HW_PHOTOS_SECRET):
 *   upload-токен  — подписывает Laravel, проверяет сервис (кому можно грузить);
 *   photo_id      — подписывает сервис, проверяет Laravel (чьё это фото);
 *   read-подпись  — подписывает Laravel, проверяет сервис (кому можно смотреть).
 * Ни одна сторона не верит браузеру на слово, сетевых вызовов между
 * Laravel и сервисом при этом не требуется.
 */

const crypto = require('node:crypto');
const fs = require('node:fs');
const fsp = require('node:fs/promises');
const path = require('node:path');
const express = require('express');
const Busboy = require('busboy');
const sharp = require('sharp');

const PORT = Number(process.env.HW_PHOTOS_PORT || 4320);
const SECRET = process.env.HW_PHOTOS_SECRET || '';
const DATA_DIR = process.env.HW_PHOTOS_DATA || '/home/dev/hw-photos-data';
const ALLOWED_ORIGINS = (process.env.HW_PHOTOS_ORIGINS
  || 'https://student.palomatika.ru,https://teacher.palomatika.ru,https://palomatika.ru')
  .split(',').map(s => s.trim()).filter(Boolean);

const MAX_BYTES = Number(process.env.HW_PHOTOS_MAX_BYTES || 25 * 1024 * 1024);
const MAX_SIDE = 2000;            // хранить больше нет смысла: это фото тетради
const THUMB_WIDTHS = [400, 800, 1600];
const UPLOADS_PER_HOUR = 30;      // на связку assignment+task+student

if (!SECRET || SECRET.length < 32) {
  console.error('HW_PHOTOS_SECRET не задан или короче 32 символов');
  process.exit(1);
}

const app = express();
app.disable('x-powered-by');
app.set('trust proxy', true);

// ── подписи ───────────────────────────────────────────────────────────────────

const b64u = buf => Buffer.from(buf).toString('base64url');
const sign = data => crypto.createHmac('sha256', SECRET).update(data).digest('base64url');

function safeEqual(a, b) {
  const ab = Buffer.from(String(a));
  const bb = Buffer.from(String(b));
  return ab.length === bb.length && crypto.timingSafeEqual(ab, bb);
}

/** Разбирает токен вида `<kind>.<payload_b64url>.<sig>`. */
function parseSigned(token, kind) {
  if (typeof token !== 'string') return null;
  const parts = token.split('.');
  if (parts.length !== 3 || parts[0] !== kind) return null;
  if (!safeEqual(parts[2], sign(parts[1]))) return null;
  try {
    return JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8'));
  } catch {
    return null;
  }
}

function makePhotoId(payload) {
  const body = b64u(JSON.stringify(payload));
  return `p.${body}.${sign(body)}`;
}

// ── хранилище ─────────────────────────────────────────────────────────────────

/**
 * Путь файла выводится из подписанного payload, а не из имени, присланного
 * браузером — обойти каталог нечем.
 */
function storagePathFor(p, suffix = '') {
  const dir = path.join(DATA_DIR, String(Number(p.a)));
  const name = [Number(p.k), Number(p.s), Number(p.t), String(p.r).replace(/[^a-z0-9]/gi, '')]
    .join('-') + suffix + '.' + (p.x === 'jpg' ? 'jpg' : String(p.x).replace(/[^a-z0-9]/gi, ''));
  return { dir, file: path.join(dir, name) };
}

const uploadCounters = new Map();

function rateLimited(key) {
  const now = Date.now();
  const rec = uploadCounters.get(key);
  if (!rec || now - rec.since > 3600_000) {
    uploadCounters.set(key, { since: now, count: 1 });
    return false;
  }
  rec.count += 1;
  return rec.count > UPLOADS_PER_HOUR;
}

setInterval(() => {
  const now = Date.now();
  for (const [key, rec] of uploadCounters) {
    if (now - rec.since > 3600_000) uploadCounters.delete(key);
  }
}, 600_000).unref();

// Сигнатуры форматов: расширение из имени файла ничего не доказывает.
const MAGIC = [
  { ext: 'jpg', test: b => b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff },
  { ext: 'png', test: b => b.subarray(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])) },
  { ext: 'gif', test: b => b.subarray(0, 3).toString('latin1') === 'GIF' },
  { ext: 'bmp', test: b => b[0] === 0x42 && b[1] === 0x4d },
  { ext: 'webp', test: b => b.subarray(0, 4).toString('latin1') === 'RIFF' && b.subarray(8, 12).toString('latin1') === 'WEBP' },
  { ext: 'heic', test: b => b.subarray(4, 8).toString('latin1') === 'ftyp' },
];

function detectExt(buf) {
  if (buf.length < 12) return null;
  for (const m of MAGIC) {
    try {
      if (m.test(buf)) return m.ext;
    } catch { /* короткий буфер */ }
  }
  return null;
}

// ── CORS ──────────────────────────────────────────────────────────────────────

app.use((req, res, next) => {
  const origin = req.headers.origin;
  if (origin && ALLOWED_ORIGINS.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
    res.setHeader('Vary', 'Origin');
    res.setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type');
    res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    res.setHeader('Access-Control-Max-Age', '600');
  }
  if (req.method === 'OPTIONS') return res.status(204).end();
  next();
});

// ── эндпоинты ─────────────────────────────────────────────────────────────────

app.get('/healthz', (req, res) => {
  res.json({ ok: true, service: 'hw-photos', uptime: Math.round(process.uptime()) });
});

/**
 * POST /v1/photos
 * Authorization: Bearer t.<payload>.<sig>   (payload: {a,k,s,e})
 * multipart/form-data, поле `photo`
 * → { photo_id, bytes, width, height }
 */
app.post('/v1/photos', (req, res) => {
  const auth = String(req.headers.authorization || '');
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : '';
  const claim = parseSigned(token, 't');

  if (!claim) return res.status(401).json({ error: 'bad_token' });
  if (!Number.isFinite(claim.e) || claim.e * 1000 < Date.now()) {
    return res.status(401).json({ error: 'token_expired' });
  }
  for (const k of ['a', 'k', 's']) {
    if (!Number.isFinite(claim[k])) return res.status(401).json({ error: 'bad_token' });
  }
  if (rateLimited(`${claim.a}-${claim.k}-${claim.s}`)) {
    return res.status(429).json({ error: 'too_many_uploads' });
  }

  let bb;
  try {
    bb = Busboy({ headers: req.headers, limits: { files: 1, fileSize: MAX_BYTES } });
  } catch {
    return res.status(400).json({ error: 'bad_request' });
  }

  const chunks = [];
  let size = 0;
  let tooLarge = false;
  let gotFile = false;
  let finished = false;

  const fail = (code, error) => {
    if (finished) return;
    finished = true;
    res.status(code).json({ error });
    req.unpipe(bb);
    req.resume();
  };

  bb.on('file', (name, stream) => {
    if (name !== 'photo') {
      stream.resume();
      return;
    }
    gotFile = true;
    stream.on('data', d => {
      size += d.length;
      chunks.push(d);
    });
    stream.on('limit', () => {
      tooLarge = true;
      fail(413, 'too_large');
    });
  });

  bb.on('error', () => fail(400, 'bad_multipart'));

  bb.on('close', async () => {
    if (finished) return;
    if (tooLarge) return fail(413, 'too_large');
    if (!gotFile || size === 0) return fail(400, 'no_file');

    const buf = Buffer.concat(chunks);
    const ext = detectExt(buf);
    if (!ext) return fail(415, 'not_an_image');

    try {
      const stored = await store(buf, ext, claim);
      finished = true;
      res.json(stored);
    } catch (err) {
      console.error('store failed', err);
      fail(500, 'store_failed');
    }
  });

  req.pipe(bb);
});

/**
 * Хранение: по возможности пережимаем в JPEG (снимает EXIF-поворот, режет вес,
 * заодно нормализует HEIC с iPhone). Если декодировать не вышло — кладём
 * оригинал: потерять фото решения хуже, чем хранить тяжёлый файл.
 */
async function store(buf, ext, claim) {
  const meta = {
    a: Number(claim.a),
    k: Number(claim.k),
    s: Number(claim.s),
    t: Math.floor(Date.now() / 1000),
    r: crypto.randomBytes(4).toString('hex'),
    x: 'jpg',
  };

  let body = buf;
  let width = null;
  let height = null;

  try {
    const img = sharp(buf, { failOn: 'none' }).rotate();
    const info = await img.metadata();
    const resize = (info.width > MAX_SIDE || info.height > MAX_SIDE)
      ? { width: MAX_SIDE, height: MAX_SIDE, fit: 'inside' }
      : null;
    const pipeline = resize ? img.resize(resize) : img;
    const out = await pipeline.jpeg({ quality: 82, mozjpeg: true }).toBuffer({ resolveWithObject: true });
    body = out.data;
    width = out.info.width;
    height = out.info.height;
  } catch (err) {
    console.warn('re-encode skipped, storing original', { ext, error: String(err && err.message) });
    meta.x = ext;
  }

  const { dir, file } = storagePathFor(meta);
  await fsp.mkdir(dir, { recursive: true, mode: 0o750 });
  await fsp.writeFile(file, body, { mode: 0o640 });

  return {
    photo_id: makePhotoId(meta),
    bytes: body.length,
    width,
    height,
    stored_as: meta.x,
  };
}

/**
 * GET /v1/photo/<photo_id>?exp=<unix>&sig=<...>[&w=400|800|1600]
 * Подпись выдаёт Laravel — только он знает, кому это фото можно показывать.
 */
app.get('/v1/photo/:id', async (req, res) => {
  const id = String(req.params.id || '');
  const exp = Number(req.query.exp);
  const sig = String(req.query.sig || '');

  if (!Number.isFinite(exp) || exp * 1000 < Date.now()) {
    return res.status(403).json({ error: 'link_expired' });
  }
  if (!safeEqual(sig, sign(`${id}.${exp}`))) {
    return res.status(403).json({ error: 'bad_signature' });
  }

  const meta = parseSigned(id, 'p');
  if (!meta) return res.status(404).json({ error: 'not_found' });

  const { file } = storagePathFor(meta);
  if (!fs.existsSync(file)) return res.status(404).json({ error: 'not_found' });

  const width = THUMB_WIDTHS.includes(Number(req.query.w)) ? Number(req.query.w) : null;
  res.setHeader('Cache-Control', 'private, max-age=600');

  if (!width || meta.x !== 'jpg') {
    return res.sendFile(file, { headers: { 'Content-Type': mimeFor(meta.x) } });
  }

  const { file: thumb } = storagePathFor(meta, `-w${width}`);
  try {
    if (!fs.existsSync(thumb)) {
      await sharp(file).resize({ width, withoutEnlargement: true })
        .jpeg({ quality: 80, mozjpeg: true }).toFile(thumb);
    }
    return res.sendFile(thumb, { headers: { 'Content-Type': 'image/jpeg' } });
  } catch (err) {
    console.warn('thumb failed, serving original', String(err && err.message));
    return res.sendFile(file, { headers: { 'Content-Type': mimeFor(meta.x) } });
  }
});

function mimeFor(ext) {
  return {
    jpg: 'image/jpeg', png: 'image/png', gif: 'image/gif',
    bmp: 'image/bmp', webp: 'image/webp', heic: 'image/heic',
  }[ext] || 'application/octet-stream';
}

app.use((req, res) => res.status(404).json({ error: 'not_found' }));

fs.mkdirSync(DATA_DIR, { recursive: true, mode: 0o750 });
app.listen(PORT, '127.0.0.1', () => {
  console.log(`hw-photos on 127.0.0.1:${PORT}, data=${DATA_DIR}`);
});
