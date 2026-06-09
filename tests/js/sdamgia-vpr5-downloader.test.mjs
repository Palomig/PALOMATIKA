import test from 'node:test';
import assert from 'node:assert/strict';

import {
  BASE_URL,
  normalizeVariantLinks,
  parseArgs,
} from '../../scripts/download-sdamgia-vpr5-pdfs.mjs';

test('normalizeVariantLinks keeps only math5 test ids and deduplicates', () => {
  const rows = normalizeVariantLinks([
    { href: `${BASE_URL}test?id=3260606`, text: 'Вариант 1', source: BASE_URL },
    { href: `${BASE_URL}test?id=3260606&foo=bar`, text: 'Дубликат', source: BASE_URL },
    { href: `${BASE_URL}test?theme=63`, text: 'Тема', source: BASE_URL },
    { href: 'https://math4-vpr.sdamgia.ru/test?id=123', text: 'Чужой предмет', source: BASE_URL },
    { href: `${BASE_URL}archive`, text: 'Архив', source: BASE_URL },
    { href: `${BASE_URL}test?id=3260610`, text: ' Вариант   5 ', source: 'archive' },
  ]);

  assert.deepEqual(rows, [
    {
      id: '3260606',
      href: `${BASE_URL}test?id=3260606`,
      label: 'Вариант 1',
      source: BASE_URL,
    },
    {
      id: '3260610',
      href: `${BASE_URL}test?id=3260610`,
      label: 'Вариант 5',
      source: 'archive',
    },
  ]);
});

test('parseArgs applies flags and validates pdf variant', () => {
  const parsed = parseArgs([
    '--current-only',
    '--pdf-variant', 'horizontal',
    '--limit', '12',
    '--delay-ms', '750',
    '--retries', '3',
  ]);

  assert.equal(parsed.includeArchive, false);
  assert.equal(parsed.pdfVariant, 'horizontal');
  assert.equal(parsed.limit, 12);
  assert.equal(parsed.delayMs, 750);
  assert.equal(parsed.retries, 3);
});
