import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import { chromium } from '/home/dev/.openclaw/workspace/node_modules/playwright-core/index.mjs';

const BASE_URL = 'https://math5-vpr.sdamgia.ru/';
const ARCHIVE_URL = 'https://math5-vpr.sdamgia.ru/archive';
const DEFAULT_OUTDIR = path.resolve(process.cwd(), 'tmp/sdamgia-math5-vpr-pdfs');
const PDF_VARIANTS = {
  vertical: 'true',
  horizontal: 'h',
};

function parseArgs(argv) {
  const options = {
    outDir: DEFAULT_OUTDIR,
    includeArchive: true,
    pdfVariant: 'vertical',
    limit: null,
    delayMs: 1500,
    retries: 5,
  };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (arg === '--out-dir' && argv[i + 1]) {
      options.outDir = path.resolve(argv[i + 1]);
      i += 1;
    } else if (arg === '--current-only') {
      options.includeArchive = false;
    } else if (arg === '--pdf-variant' && argv[i + 1]) {
      options.pdfVariant = argv[i + 1];
      i += 1;
    } else if (arg === '--limit' && argv[i + 1]) {
      options.limit = Number.parseInt(argv[i + 1], 10);
      i += 1;
    } else if (arg === '--delay-ms' && argv[i + 1]) {
      options.delayMs = Number.parseInt(argv[i + 1], 10);
      i += 1;
    } else if (arg === '--retries' && argv[i + 1]) {
      options.retries = Number.parseInt(argv[i + 1], 10);
      i += 1;
    }
  }

  if (!PDF_VARIANTS[options.pdfVariant]) {
    throw new Error(`Unsupported --pdf-variant: ${options.pdfVariant}`);
  }

  if (options.limit !== null && (!Number.isInteger(options.limit) || options.limit <= 0)) {
    throw new Error(`Invalid --limit: ${options.limit}`);
  }

  if (!Number.isInteger(options.delayMs) || options.delayMs < 0) {
    throw new Error(`Invalid --delay-ms: ${options.delayMs}`);
  }

  if (!Number.isInteger(options.retries) || options.retries < 0) {
    throw new Error(`Invalid --retries: ${options.retries}`);
  }

  return options;
}

function normalizeVariantLinks(records) {
  const seen = new Set();
  const normalized = [];

  for (const record of records) {
    if (!record?.href) continue;

    const url = new URL(record.href, BASE_URL);
    if (url.hostname !== 'math5-vpr.sdamgia.ru') continue;
    if (url.pathname !== '/test') continue;

    const id = url.searchParams.get('id');
    if (!id || !/^\d+$/.test(id)) continue;
    if (seen.has(id)) continue;

    seen.add(id);
    normalized.push({
      id,
      href: `${BASE_URL}test?id=${id}`,
      label: cleanLabel(record.text) || `Вариант ${id}`,
      source: record.source || BASE_URL,
    });
  }

  return normalized.sort((a, b) => Number(a.id) - Number(b.id));
}

function cleanLabel(text) {
  return String(text || '')
    .replace(/\s+/g, ' ')
    .replace(/\u00a0/g, ' ')
    .trim();
}

async function collectPageLinks(page, url) {
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForTimeout(2500);

  return page.locator('a').evaluateAll((anchors, sourceUrl) => (
    anchors.map((anchor) => ({
      href: anchor.href,
      text: (anchor.textContent || '').replace(/\s+/g, ' ').trim(),
      source: sourceUrl,
    }))
  ), url);
}

async function collectVariantLinks() {
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
  });

  try {
    const page = await browser.newPage();
    const mainLinks = await collectPageLinks(page, BASE_URL);
    const archiveLinks = await collectPageLinks(page, ARCHIVE_URL);
    return normalizeVariantLinks([...mainLinks, ...archiveLinks]);
  } finally {
    await browser.close();
  }
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function fileExists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

async function downloadPdf(page, entry, pdfVariant, outDir, options) {
  const pdfParam = PDF_VARIANTS[pdfVariant];
  const pdfDir = path.join(outDir, 'pdf');
  await ensureDir(pdfDir);

  const fileName = `${entry.id}-${pdfVariant}.pdf`;
  const filePath = path.join(pdfDir, fileName);

  if (await fileExists(filePath)) {
    const stats = await fs.stat(filePath);
    return {
      ...entry,
      pdfVariant,
      pdfUrl: null,
      fileName,
      filePath,
      bytes: stats.size,
      skipped: true,
    };
  }

  let lastError = null;
  for (let attempt = 0; attempt <= options.retries; attempt += 1) {
    if (attempt > 0) {
      await sleep(options.delayMs * (attempt + 1));
    }

    try {
      const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 60000 }),
        page.goto(`${entry.href}&print=true&pdf=${pdfParam}`, { waitUntil: 'domcontentloaded', timeout: 60000 }),
      ]);

      await download.saveAs(filePath);
      const stats = await fs.stat(filePath);

      return {
        ...entry,
        pdfVariant,
        pdfUrl: download.url(),
        fileName,
        filePath,
        bytes: stats.size,
        skipped: false,
      };
    } catch (error) {
      lastError = error;
      const message = error?.message || '';
      if (!message.includes('403') && !message.includes('429') && !message.includes('timeout')) {
        break;
      }
    }
  }

  throw lastError ?? new Error('Unknown download error');
}

async function writeManifest(outDir, rows) {
  const manifestPath = path.join(outDir, 'manifest.json');
  const csvPath = path.join(outDir, 'manifest.csv');

  await fs.writeFile(manifestPath, JSON.stringify(rows, null, 2));

  const csvHeader = 'id,label,variant_url,pdf_url,file_name,bytes,source\n';
  const csvRows = rows.map((row) => [
    row.id,
    csvEscape(row.label),
    csvEscape(row.href),
    csvEscape(row.pdfUrl),
    csvEscape(row.fileName),
    row.bytes,
    csvEscape(row.source),
  ].join(','));
  await fs.writeFile(csvPath, csvHeader + csvRows.join('\n') + '\n');

  return { manifestPath, csvPath };
}

function csvEscape(value) {
  const text = String(value ?? '');
  return `"${text.replaceAll('"', '""')}"`;
}

async function main() {
  const options = parseArgs(process.argv.slice(2));
  await ensureDir(options.outDir);

  let variants = await collectVariantLinks();
  if (!options.includeArchive) {
    const currentIds = new Set(
      variants
        .filter((variant) => variant.source === BASE_URL)
        .map((variant) => variant.id),
    );
    variants = variants.filter((variant) => currentIds.has(variant.id));
  }
  if (options.limit !== null) {
    variants = variants.slice(0, options.limit);
  }

  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
  });
  const context = await browser.newContext({
    acceptDownloads: true,
  });
  const page = await context.newPage();

  console.log(`Found ${variants.length} unique variants`);
  console.log(`Output: ${options.outDir}`);

  const downloaded = [];
  try {
    for (const variant of variants) {
      try {
        const row = await downloadPdf(page, variant, options.pdfVariant, options.outDir, options);
        downloaded.push(row);
        if (row.skipped) {
          console.log(`SKIP ${variant.id} -> ${row.fileName}`);
        } else {
          console.log(`OK ${variant.id} -> ${row.fileName}`);
        }
        await sleep(options.delayMs);
      } catch (error) {
        console.error(`FAIL ${variant.id}: ${error.message}`);
      }
    }
  } finally {
    await context.close();
    await browser.close();
  }

  const { manifestPath, csvPath } = await writeManifest(options.outDir, downloaded);
  console.log(`Downloaded ${downloaded.length}/${variants.length} PDFs`);
  console.log(`Manifest JSON: ${manifestPath}`);
  console.log(`Manifest CSV: ${csvPath}`);
}

const scriptPath = process.argv[1] ? pathToFileURL(path.resolve(process.argv[1])).href : null;
if (scriptPath && import.meta.url === scriptPath) {
  main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
  });
}

export {
  BASE_URL,
  ARCHIVE_URL,
  PDF_VARIANTS,
  cleanLabel,
  normalizeVariantLinks,
  parseArgs,
};
