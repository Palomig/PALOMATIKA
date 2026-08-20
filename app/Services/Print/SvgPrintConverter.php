<?php

namespace App\Services\Print;

use Illuminate\Support\Facades\Log;

/**
 * Готовит SVG банка к печати: перекрашивает тёмную экранную палитру в чёрно-белую
 * и конвертирует в PDF, который включается в вариант через \includegraphics.
 *
 * Вектор сохраняется — растеризации нет ни на одном шаге, поэтому чертёж
 * печатается с разрешением принтера, а не экрана.
 */
class SvgPrintConverter
{
    /**
     * Экранная палитра банка → печатная. Собрана перебором всех SVG банка ОГЭ,
     * пересчитывать при добавлении новых стилей: см. docs/print-variants.md.
     */
    private const PALETTE = [
        '#0a1628' => '#ffffff', // фон подложки
        '#c8dce8' => '#000000', // основные линии и подписи
        '#7eb8da' => '#000000', // засечки на осях
        '#8ec9ff' => '#000000', // отмеченные точки
        '#6cb6ee' => '#000000', // график функции
        '#e8a838' => '#000000', // выделение (штриховка, отрезок-ответ)
        '#526b7d' => '#808080', // клетчатая сетка
        '#1a3a5c' => '#808080', // координатная сетка
        '#1e3a5f' => '#808080',
        '#2c5378' => '#606060',
        '#3f6f96' => '#404040',
    ];

    /** Экранный шрифт подписей на печати заменяется на Computer Modern. */
    private const FONT_MAP = [
        'Times New Roman, Times, serif' => 'CMU Serif, Times, serif',
        '"Times New Roman",serif' => '"CMU Serif", serif',
        'Times New Roman' => 'CMU Serif',
    ];

    public function __construct(private readonly string $workDir)
    {
    }

    /**
     * @return array{path: string, width: float, height: float}|null
     *         Путь к PDF и его натуральный размер в пунктах.
     */
    public function convert(string $svgMarkup, string $basename): ?array
    {
        $svg = $this->recolor($svgMarkup);

        $svgPath = $this->workDir . '/' . $basename . '.svg';
        $pdfPath = $this->workDir . '/' . $basename . '.pdf';

        if (!is_dir($this->workDir) && !mkdir($this->workDir, 0775, true) && !is_dir($this->workDir)) {
            throw new \RuntimeException("Не удалось создать каталог {$this->workDir}");
        }

        file_put_contents($svgPath, $svg);

        $cmd = sprintf(
            'rsvg-convert -f pdf -o %s %s 2>&1',
            escapeshellarg($pdfPath),
            escapeshellarg($svgPath)
        );
        exec($cmd, $output, $code);

        if ($code !== 0 || !is_file($pdfPath) || filesize($pdfPath) === 0) {
            Log::warning('SVG для печати не сконвертировался', [
                'basename' => $basename,
                'code' => $code,
                'output' => implode("\n", $output),
            ]);

            return null;
        }

        [$w, $h] = $this->naturalSize($svg);

        return ['path' => $pdfPath, 'width' => $w, 'height' => $h];
    }

    /**
     * Перекраска экранной палитры в печатную.
     *
     * Подложка не перекрашивается в белый, а удаляется: белый прямоугольник
     * во всю картинку печатается как заливка и на цветном бланке даёт заметную
     * рамку вокруг чертежа.
     */
    private function recolor(string $svg): string
    {
        $svg = preg_replace(
            '/<rect\b[^>]*width="100%"[^>]*height="100%"[^>]*\/>/i',
            '',
            $svg
        ) ?? $svg;

        foreach (self::PALETTE as $from => $to) {
            $svg = str_ireplace($from, $to, $svg);
        }

        foreach (self::FONT_MAP as $from => $to) {
            $svg = str_replace($from, $to, $svg);
        }

        // Классы экранной темы задаются через <style> внутри SVG; после замены
        // цветов там остаются только имена — их не трогаем, палитра уже другая.
        return $svg;
    }

    /** Натуральный размер из viewBox или атрибутов width/height. */
    private function naturalSize(string $svg): array
    {
        if (preg_match('/viewBox="\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s*"/i', $svg, $m)) {
            return [(float) $m[3], (float) $m[4]];
        }

        $w = preg_match('/\bwidth="([\d.]+)/i', $svg, $mw) ? (float) $mw[1] : 200.0;
        $h = preg_match('/\bheight="([\d.]+)/i', $svg, $mh) ? (float) $mh[1] : 160.0;

        return [$w, $h];
    }
}
