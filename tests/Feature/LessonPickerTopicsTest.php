<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Темы ОГЭ с типами word_problem / geometry / grid_image / statements должны
 * отображаться в конструкторе урока и резолвиться при добавлении (текст/картинка
 * + ответ свободным текстом).
 */
class LessonPickerTopicsTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider topicProvider */
    public function test_oge_topic_yields_resolvable_tasks(string $topicId): void
    {
        $picker = new LessonTaskPickerService();
        $resolver = new TaskBankResolver();

        $tasks = $picker->tasks('oge', ['topic_id' => $topicId]);
        $this->assertNotEmpty($tasks, "Тема {$topicId} не отдала ни одной задачи в picker");

        $first = $tasks[0];
        $resolved = $resolver->resolve('oge', [
            'topic_id'       => $topicId,
            'zadanie_number' => $first['zadanie_number'],
            'task_id'        => $first['id'],
        ]);

        $this->assertContains($resolved['type'], ['expression', 'choice']);
        $this->assertNotSame('', (string) $resolved['answer'], "Тема {$topicId}: пустой ответ");
        $this->assertNotSame('', (string) $resolved['expression'], "Тема {$topicId}: пустое условие");
    }

    public static function topicProvider(): array
    {
        // по одному представителю каждого типа + полный список из запроса
        return array_map(fn ($t) => [$t], ['10', '12', '14', '15', '16', '17', '18', '19', '20', '21', '23']);
    }
}
