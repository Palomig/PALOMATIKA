<?php

namespace App\Console\Commands;

use App\Services\Print\HtmlToLatexConverter;
use App\Services\Print\PrintAssetStore;
use App\Services\Print\PrintVariantComposer;
use App\Services\Print\PrintVariantSelector;
use App\Services\Print\SvgPrintConverter;
use Illuminate\Console\Command;

/**
 * Генерация печатных вариантов ОГЭ из банка заданий.
 *
 * pdflatex на проде нет и не предполагается — команда рассчитана на запуск
 * на dev-VPS или локально, результат (PDF) выкладывается отдельно.
 */
class GeneratePrintVariants extends Command
{
    protected $signature = 'oge:print
        {--count=1 : сколько вариантов сгенерировать}
        {--seed= : базовый seed; по умолчанию случайный}
        {--first=1 : номер первого варианта в нумерации работ}
        {--topics= : список тем через запятую, например 06,07,15}
        {--out= : каталог результата, по умолчанию storage/app/print}
        {--title=Тренировочная работа : заголовок работы без номера}
        {--head-left=ОГЭ--2026 : левый колонтитул}
        {--keep-tex : оставить .tex и промежуточные файлы}
        {--no-pdf : только собрать .tex, не запускать pdflatex}';

    protected $description = 'Собрать печатные варианты ОГЭ из банка заданий';

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $first = max(1, (int) $this->option('first'));
        $seed = $this->option('seed') !== null ? (int) $this->option('seed') : random_int(1, 999999);
        $outDir = rtrim((string) ($this->option('out') ?: storage_path('app/print')), '/');
        $topics = $this->parseTopics((string) $this->option('topics'));

        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            $this->error("Не удалось создать каталог {$outDir}");

            return self::FAILURE;
        }

        $withPdf = !$this->option('no-pdf');
        if ($withPdf && !$this->hasPdfLatex()) {
            $this->error('pdflatex не найден. Поставьте TeXLive или запустите с --no-pdf.');

            return self::FAILURE;
        }

        $selector = new PrintVariantSelector();
        $assets = new PrintAssetStore(
            (string) config('print.assets_cache', storage_path('app/print-assets')),
            (string) config('print.assets_url', 'https://palomig.ru/fipi-bank-export'),
        );
        $keys = [];
        $allUnknown = [];

        for ($i = 0; $i < $count; $i++) {
            $number = $first + $i;
            $variantSeed = $seed + $i;

            $work = $outDir . '/build-' . $number;
            $this->prepareWorkdir($work);

            $composer = new PrintVariantComposer(
                // Конвертеру нужен только размер растра — чтобы отличить
                // формулу внутри предложения от самостоятельного чертежа.
                new HtmlToLatexConverter(
                    static fn (string $rel): ?float => $assets->describe($rel)['height'] ?? null
                ),
                new SvgPrintConverter($work),
                $assets,
            );

            $items = $selector->select($variantSeed, $topics);
            if ($items === []) {
                $this->error('Банк не отдал ни одного задания — проверьте фильтры тем.');

                return self::FAILURE;
            }

            $built = $composer->compose(
                $items,
                (string) $this->option('title') . ' \\textnumero{} ' . $number,
                (string) $this->option('head-left'),
                (string) $this->option('title') . ' \\textnumero{} ' . $number,
                $selector->intro(),
            );

            file_put_contents($work . '/variant.tex', $built['tex']);

            $keys[$number] = [
                'seed' => $variantSeed,
                'answers' => $built['answers'],
                'tasks' => array_map(
                    static fn (array $it): array => ['n' => $it['number'], 'task_id' => $it['task']->id],
                    $items
                ),
            ];

            $unknown = $composer->unknownChars();
            if ($unknown !== []) {
                $allUnknown += $unknown;
                $this->warn("вариант {$number}: незнакомые символы заменены на «?» — " . implode(' ', array_keys($unknown)));
            }

            $line = "вариант {$number}: заданий " . count($items) . ', seed ' . $variantSeed;

            if ($withPdf) {
                $pdf = $this->runPdfLatex($work);
                if ($pdf === null) {
                    $this->error("вариант {$number}: pdflatex не собрал документ, лог в {$work}/variant.log");

                    return self::FAILURE;
                }
                $final = $outDir . sprintf('/variant-%03d.pdf', $number);
                copy($pdf, $final);
                $line .= ', ' . basename($final) . ' (' . $this->pageCount($final) . ' стр.)';
            }

            $this->info($line);

            if (!$this->option('keep-tex') && $withPdf) {
                $this->removeDir($work);
            }
        }

        if ($allUnknown !== []) {
            $this->warn('символы, которых нет в таблице перевода: ' . implode(' ', array_keys($allUnknown))
                . ' — добавьте их в HtmlToLatexConverter::escape()');
        }

        $keyPath = $outDir . '/answers.json';
        file_put_contents($keyPath, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('ключ с ответами: ' . $keyPath);

        return self::SUCCESS;
    }

    /** @return list<string>|null */
    private function parseTopics(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $topics = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $topics[] = str_pad($part, 2, '0', STR_PAD_LEFT);
        }

        return $topics === [] ? null : $topics;
    }

    private function prepareWorkdir(string $work): void
    {
        $this->removeDir($work);
        mkdir($work, 0775, true);

        foreach (['preamble.tex', 'params.tex'] as $file) {
            copy(resource_path('latex/oge/' . $file), $work . '/' . $file);
        }
    }

    private function hasPdfLatex(): bool
    {
        exec('command -v pdflatex 2>/dev/null', $out, $code);

        return $code === 0;
    }

    /**
     * Два прогона: первый расставляет номера страниц и метки, второй их
     * фиксирует. На одном прогоне колонтитул последней страницы может уехать.
     */
    private function runPdfLatex(string $work): ?string
    {
        for ($pass = 0; $pass < 2; $pass++) {
            exec(sprintf(
                'cd %s && pdflatex -interaction=nonstopmode -halt-on-error variant.tex > pdflatex.out 2>&1',
                escapeshellarg($work)
            ), $out, $code);

            if ($code !== 0) {
                return null;
            }
        }

        $pdf = $work . '/variant.pdf';

        return is_file($pdf) ? $pdf : null;
    }

    private function pageCount(string $pdf): int
    {
        exec(sprintf('pdfinfo %s 2>/dev/null | grep -m1 ^Pages', escapeshellarg($pdf)), $out);

        return isset($out[0]) ? (int) filter_var($out[0], FILTER_SANITIZE_NUMBER_INT) : 0;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
