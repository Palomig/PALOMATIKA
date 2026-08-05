/**
 * Браузерный смоук сдачи фото-домашки.
 *
 * Зачем: сдача держится на Alpine-коде во вью (сжатие, загрузка страниц в
 * hw-photos, подстановка photo_ids либо возврат файлов в форму). Ни phpunit,
 * ни curl этот слой не трогают — 31.07.2026 из-за этого на прод уехала
 * поломка, при которой фото уже загружено, а строка вечно висит «загружаем…».
 * После правок в `homework-topic-practice.blade.php` гонять обязательно.
 *
 * Запуск (нужен QA-ученик и выданное ему ДЗ типа topic_photo_practice):
 *   node tests/browser/homework-photo-smoke.mjs <assignment_id> photo1.jpg [photo2.jpg …]
 *
 * Переменные окружения:
 *   ANSWER=12          — какой ответ вписать (по умолчанию 12)
 *   BLOCK_STORE=1      — хранилище недоступно: проверяем фолбэк на файлы
 *   BLOCK_STORE=after1 — сеть отвалилась на середине набора страниц
 *   CHROME=/path/chrome, DEPLOY_SECRET=…
 *
 * Что должно быть в выводе: «JS-ошибок не было», у страниц статус
 * «загружено» (или «уйдёт с формой» в фолбэке) и плашка о принятой задаче.
 */
import puppeteer from 'puppeteer-core';

const CHROME = process.env.CHROME || '/home/dev/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
const SECRET = process.env.DEPLOY_SECRET || '';
const ASSIGNMENT = process.argv[2];
const PHOTOS = process.argv.slice(3);

// Режим «сервис недоступен»: блокируем запросы к хранилищу, чтобы проверить фолбэк.
const BLOCK_STORE = process.env.BLOCK_STORE || '';

if (!SECRET || !ASSIGNMENT || PHOTOS.length === 0) {
  console.error('Нужны DEPLOY_SECRET, id назначения и хотя бы одно фото.');
  process.exit(2);
}

const browser = await puppeteer.launch({
  executablePath: CHROME,
  headless: true,
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const page = await browser.newPage();
await page.setViewport({ width: 390, height: 780, isMobile: true, hasTouch: true });

// BLOCK_STORE=1 — хранилище недоступно совсем; BLOCK_STORE=after1 — отвалилось
// на середине: первая страница уехала, вторая нет (флакающая сеть).
if (BLOCK_STORE) {
  let uploads = 0;
  await page.setRequestInterception(true);
  page.on('request', r => {
    const isUpload = r.url().includes('palomig.ru/hw-photos/v1/photos');
    if (!isUpload) return r.continue();
    uploads++;
    if (BLOCK_STORE === 'after1' && uploads === 1) return r.continue();
    return r.abort();
  });
}

const problems = [];
page.on('console', m => {
  const t = m.type();
  if (t === 'error' || t === 'warning') console.log(`  [console.${t}] ${m.text()}`);
});
page.on('pageerror', e => {
  problems.push(String(e.message));
  console.log(`  [JS ERROR] ${e.message}`);
});
page.on('requestfailed', r => console.log(`  [запрос упал] ${r.method()} ${r.url()} — ${r.failure()?.errorText}`));
page.on('response', async r => {
  const u = r.url();
  if (u.includes('/homework/') || u.includes('/hw-photos/')) {
    console.log(`  [${r.status()}] ${r.request().method()} ${u.replace('https://', '')}`);
  }
});

console.log('1) вход под QA-учеником');
await page.goto(`https://student.palomatika.ru/qa-login?secret=${SECRET}&email=qa-student@palomatika.ru&redirect=/homework/${ASSIGNMENT}`,
  { waitUntil: 'networkidle2', timeout: 60000 });

if (page.url().includes('link-telegram')) {
  await page.evaluate(() => document.querySelector('form[action*="snooze"]')?.submit());
  await new Promise(r => setTimeout(r, 1500));
  await page.goto(`https://student.palomatika.ru/homework/${ASSIGNMENT}`, { waitUntil: 'networkidle2' });
}

console.log('   страница:', page.url());

console.log(`2) прикладываю ${PHOTOS.length} фото`);
const input = await page.$('input[type=file]');
if (!input) {
  console.log('   ФОРМЫ НЕТ — задача уже принята?');
  await browser.close();
  process.exit(1);
}
await input.uploadFile(...PHOTOS);

// EARLY_SUBMIT=1 — ученик жмёт «Отправить», не дожидаясь загрузки фото (так и
// ведут себя на телефоне). Форма обязана уйти сама, когда страницы догрузятся.
if (process.env.EARLY_SUBMIT) {
  console.log('   (жму «Отправить» сразу, не дожидаясь загрузки)');
  await page.type('input[name=answer]', process.env.ANSWER || '12');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 180000 })
      .catch(() => console.log('   НАВИГАЦИИ НЕ БЫЛО — раннее нажатие потеряно')),
    page.click('.submit-btn'),
  ]);
  await new Promise(r => setTimeout(r, 1500));
  console.log('раннее нажатие — итог:', JSON.stringify(await page.evaluate(() => ({
    плашка: document.querySelector('.notice')?.textContent?.trim(),
    состояния: [...document.querySelectorAll('.task-state')].map(e => e.textContent.trim()),
  }))));
  console.log(problems.length ? `\nJS-ОШИБКИ: ${problems.join(' | ')}` : '\nJS-ошибок не было');
  await browser.close();
  process.exit(0);
}

// Ждём, пока страницы догрузятся в хранилище. Состояние смотрим по разметке:
// у Alpine 3 нет `__x`, и прежняя проверка проходила мгновенно, не дожидаясь
// ничего — из-за этого смоук не видел, что кнопка ещё заблокирована.
await page.waitForFunction(
  () => {
    const form = document.querySelector('form.task-form');
    if (!form) return false;
    const states = [...form.querySelectorAll('.page-row .page-state')].map(e => e.textContent.trim());
    return states.length > 0 && states.every(s => s !== 'загружаем…');
  },
  { timeout: 180000, polling: 300 },
).catch(() => console.log('   (не дождался конца загрузки — смотрю состояние как есть)'));
await new Promise(r => setTimeout(r, 1500));

const state = await page.evaluate(() => {
  const form = document.querySelector('form.task-form');
  const hidden = [...form.querySelectorAll('input[name="photo_ids[]"]')].map(i => i.value.slice(0, 18) + '…');
  const fileInput = form.querySelector('input[type=file]');
  const btn = form.querySelector('.submit-btn');
  return {
    подписьКнопки: form.querySelector('.photo-label span[x-text]')?.textContent?.trim(),
    подсказка: form.querySelector('.photo-hint')?.textContent?.trim(),
    строкиСтраниц: [...form.querySelectorAll('.page-row')].map(r => r.textContent.replace(/\s+/g, ' ').trim()),
    скрытыеPhotoIds: hidden,
    файловВинпуте: fileInput.files.length,
    кнопкаТекст: btn?.textContent?.trim(),
    кнопкаЗаблокирована: btn?.disabled,
  };
});
console.log('3) состояние формы:', JSON.stringify(state, null, 2));

console.log('4) вписываю ответ и жму «Отправить»');
await page.type('input[name=answer]', process.env.ANSWER || '12');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }).catch(() => console.log('   НАВИГАЦИИ НЕ БЫЛО — форма не отправилась')),
  page.click('.submit-btn'),
]);

await new Promise(r => setTimeout(r, 1500));
const after = await page.evaluate(() => ({
  url: location.pathname,
  плашка: document.querySelector('.notice')?.textContent?.trim(),
  состояния: [...document.querySelectorAll('.task-state')].map(e => e.textContent.trim()),
  модалка: !!document.querySelector('.hw-modal'),
}));
console.log('5) после отправки:', JSON.stringify(after, null, 2));
console.log(problems.length ? `\nJS-ОШИБКИ: ${problems.join(' | ')}` : '\nJS-ошибок не было');

await browser.close();
