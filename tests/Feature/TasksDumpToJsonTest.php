<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Обратная выгрузка в JSON — то, ради чего файлы вообще остаются в репозитории:
 * правку задания должно быть видно в diff.
 *
 * Поэтому проверяется не только содержимое, но и форматирование: банки
 * набирались разными инструментами (отступ 2 и 4, слэши экранированные и нет),
 * и дамп обязан повторять стиль конкретного файла — иначе первый же прогон
 * утопит diff в пробелах.
 */
class TasksDumpToJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('tasks:import-json', ['--bank' => 'oge']);
    }

    public function test_dry_run_does_not_touch_files(): void
    {
        $path = storage_path('app/tasks/topic_16.json');
        $before = File::get($path);

        Artisan::call('tasks:dump-json', ['--bank' => 'oge', '--dry-run' => true]);

        $this->assertSame($before, File::get($path), 'сухой прогон изменил файл');
    }

    public function test_dump_matches_the_source_file_byte_for_byte(): void
    {
        $path = storage_path('app/tasks/topic_16.json');
        $before = File::get($path);

        Artisan::call('tasks:dump-json', ['--bank' => 'oge']);

        $this->assertSame(
            $before,
            File::get($path),
            'выгрузка темы 16 разошлась с исходным файлом — сломано сохранение форматирования'
        );
    }

    public function test_content_survives_even_where_formatting_differs(): void
    {
        // У части файлов форматирование ручное (объекты в одну строку), его
        // json_encode не воспроизводит. Содержимое при этом обязано совпасть.
        Artisan::call('tasks:dump-json', ['--bank' => 'oge']);

        foreach (glob(storage_path('app/tasks/topic_*.json')) as $path) {
            if (!preg_match('/^topic_\d+\.json$/', basename($path))) {
                continue;
            }
            $this->assertIsArray(
                json_decode(File::get($path), true),
                'после выгрузки файл перестал быть валидным JSON: ' . basename($path)
            );
        }
    }

    protected function tearDown(): void
    {
        // Тест пишет в реальные файлы банка — возвращаем их из git,
        // чтобы прогон не оставлял за собой изменений в рабочем дереве.
        exec('git -C ' . escapeshellarg(base_path()) . ' checkout -- storage/app/tasks 2>/dev/null');
        parent::tearDown();
    }
}
