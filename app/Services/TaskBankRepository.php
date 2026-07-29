<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use Illuminate\Support\Facades\Schema;

/**
 * Чтение банка заданий из БД в том же виде, в каком он лежал в JSON.
 *
 * Сервисы банков (`TaskDataService` и соседи) продолжают работать со
 * структурой «topic_id / meta / blocks → zadaniya → tasks» — репозиторий
 * собирает её из таблиц, а не из файла.
 *
 * `hasData()` — точка переключения. Пока таблиц нет или тема в них не
 * заполнена, сервис читает файл, как раньше. Благодаря этому выкладка кода
 * и собственно переезд данных — два независимых события: деплой ничего не
 * ломает, даже если миграции ещё не прогнаны.
 */
class TaskBankRepository
{
    private static ?bool $tablesExist = null;

    /** Заполнена ли тема в базе. */
    public function hasData(string $bank, string $topic, ?int $grade = null): bool
    {
        if (!$this->tablesExist()) {
            return false;
        }

        return TaskGroup::query()
            ->where('bank', $bank)
            ->where('grade', $grade)
            ->where('topic', $topic)
            ->exists();
    }

    /**
     * Тема целиком: верхнеуровневые поля из `task_topics` плюс блоки,
     * собранные из `task_groups` и `tasks`.
     */
    public function topicData(string $bank, string $topic, ?int $grade = null): array
    {
        $meta = TaskTopic::query()
            ->where('bank', $bank)->where('grade', $grade)->where('topic', $topic)
            ->value('payload') ?? [];

        $groups = TaskGroup::query()
            ->with('tasks')
            ->where('bank', $bank)
            ->where('grade', $grade)
            ->where('topic', $topic)
            ->orderBy('position')
            ->get();

        // Задания идут подряд и уже упорядочены; блок начинается там, где
        // сменился его номер. Порядок блоков в файле кураторский, и
        // группировка по номеру его бы перетасовала.
        $blocks = [];
        $current = null;
        foreach ($groups as $group) {
            if ($current === null || $current['number'] !== $group->block_number) {
                if ($current !== null) {
                    $blocks[] = $current;
                }
                $current = array_filter([
                    'number' => $group->block_number,
                    'title' => $group->block_title,
                ], static fn ($v) => $v !== null);
                $current['zadaniya'] = [];
            }
            $current['zadaniya'][] = $this->zadanie($group);
        }
        if ($current !== null) {
            $blocks[] = $current;
        }

        return array_merge($meta, ['blocks' => $blocks]);
    }

    private function zadanie(TaskGroup $group): array
    {
        // Инструкция, тип и статус лежат и в колонках, и в payload; берём из
        // payload, чтобы не дописать ключ, которого в исходнике не было.
        $zadanie = array_merge(['number' => $group->zadanie_number], $group->payload ?? []);
        $zadanie['tasks'] = $group->tasks->map(fn (Task $task) => $task->payload ?? [])->all();

        return $zadanie;
    }

    /**
     * Есть ли таблицы вообще. Проверяется один раз за процесс: на каждое
     * чтение темы ходить в information_schema дорого, а появиться посреди
     * запроса таблицы не могут.
     */
    private function tablesExist(): bool
    {
        if (self::$tablesExist === null) {
            try {
                self::$tablesExist = Schema::hasTable('task_groups');
            } catch (\Throwable) {
                self::$tablesExist = false;   // база недоступна — работаем с файлами
            }
        }

        return self::$tablesExist;
    }

    /** Для тестов: сбросить запомненное наличие таблиц. */
    public static function forgetTableCheck(): void
    {
        self::$tablesExist = null;
    }
}
