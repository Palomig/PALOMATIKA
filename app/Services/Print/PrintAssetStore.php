<?php

namespace App\Services\Print;

use Illuminate\Support\Facades\Log;

/**
 * Локальный кэш иллюстраций банка для печати.
 *
 * Растровые картинки вводных текстов лежат не в базе, а рядом с выгрузкой ФИПИ.
 * Хранилище скачивает их один раз и переиспользует: генерация сотни вариантов
 * не должна сто раз ходить в сеть за одним и тем же планом участка.
 */
class PrintAssetStore
{
    public function __construct(
        private readonly string $cacheDir,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Локальный файл вместе с натуральным размером в пунктах.
     *
     * Размер нужен, чтобы отличить обозначение внутри предложения от
     * самостоятельного чертежа: у ФИПИ часть формул хранится растром, и по
     * разметке они неотличимы от плана участка — только по высоте.
     *
     * @return array{path: string, width: float, height: float}|null
     */
    public function describe(string $relative): ?array
    {
        $path = $this->localPath($relative);
        if ($path === null) {
            return null;
        }

        $size = @getimagesize($path);
        if ($size === false) {
            return null;
        }

        // Экранный пиксель к пункту: 96 dpi против 72. Растр печатается в своём
        // размере, увеличение превращает 262 px в мыло на пол-страницы.
        return [
            'path' => $path,
            'width' => $size[0] * 0.75,
            'height' => $size[1] * 0.75,
        ];
    }

    /**
     * Возвращает путь к локальному файлу, пригодному для \includegraphics.
     *
     * GIF конвертируется в PNG: pdflatex его не понимает, а три плана
     * местности в банке сохранены именно гифами.
     */
    public function localPath(string $relative): ?string
    {
        $relative = ltrim($relative, '/');
        $target = $this->cacheDir . '/' . $relative;

        if (!is_file($target) && !$this->download($relative, $target)) {
            return null;
        }

        if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) === 'gif') {
            return $this->gifToPng($target);
        }

        return $target;
    }

    private function download(string $relative, string $target): bool
    {
        $dir = dirname($target);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $url = rtrim($this->baseUrl, '/') . '/' . $relative;
        $body = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 30],
        ]));

        if ($body === false || $body === '') {
            Log::warning('Иллюстрация для печати не скачалась', ['url' => $url]);

            return false;
        }

        return file_put_contents($target, $body) !== false;
    }

    private function gifToPng(string $gif): ?string
    {
        $png = preg_replace('/\.gif$/i', '.png', $gif);
        if ($png === null) {
            return null;
        }

        if (is_file($png)) {
            return $png;
        }

        $image = @imagecreatefromgif($gif);
        if ($image === false) {
            Log::warning('GIF не открылся', ['file' => $gif]);

            return null;
        }

        $ok = imagepng($image, $png);
        imagedestroy($image);

        return $ok ? $png : null;
    }
}
