<?php

/**
 * Проверка, что Blade-шаблоны вообще собираются в валидный PHP.
 *
 * Ловит класс ошибок, невидимый при чтении диффа: HTML-атрибут, который Blade
 * принимает за свою директиву. Живой пример — Alpine-обработчик @error="…" на
 * <img>: компилятор развернул его в незакрытый if из ViewErrorBag, шаблон
 * перестал парситься, и все страницы с этим партиалом отдали 500. На глаз в
 * ревью такое не видно, а `php artisan view:cache` компилирует молча — он не
 * проверяет результат на синтаксис.
 *
 * Использование: php scripts/blade-lint.php <файл.blade.php> [ещё файлы…]
 * Код возврата: 0 — все собрались, 1 — нет.
 */

$autoload = null;
foreach ([__DIR__ . '/../vendor/autoload.php', '/home/dev/palomatika/vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "blade-lint: не найден vendor/autoload.php — проверка пропущена\n");
    exit(0);
}

require $autoload;

$files = array_slice($argv, 1);
if ($files === []) {
    exit(0);
}

$compiler = new Illuminate\View\Compilers\BladeCompiler(
    new Illuminate\Filesystem\Filesystem,
    sys_get_temp_dir()
);

$broken = 0;

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'blade-lint') . '.php';
    file_put_contents($tmp, $compiler->compileString(file_get_contents($file)));

    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $code);
    unlink($tmp);

    if ($code !== 0) {
        $broken++;
        $message = preg_replace(
            ['/ in \S+ on line (\d+)/', '/Errors parsing \S+/'],
            [' (строка $1 собранного файла)', ''],
            trim(implode(' ', $output))
        );
        fwrite(STDERR, "НЕ СОБИРАЕТСЯ: $file\n   $message\n");
    }
}

exit($broken > 0 ? 1 : 0);
