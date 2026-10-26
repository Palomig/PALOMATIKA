<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Models\User;
use App\Services\LessonHomeworkSuggestionService;
use App\Services\LessonSessionService;
use App\Services\TaskBankRepository;
use App\Services\TaskAnswerResolver;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LessonHomeworkSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function lessonService(): LessonSessionService
    {
        return new LessonSessionService(new TaskBankResolver(), new TaskAnswerResolver());
    }

    private function suggestionService(): LessonHomeworkSuggestionService
    {
        return new LessonHomeworkSuggestionService(new TaskBankResolver());
    }

    private function teacher(): User
    {
        return User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
        ]);
    }

    private function skillRefs(int $taskId): array
    {
        return ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => $taskId];
    }

    public function test_groups_lesson_tasks_and_excludes_used_task_ids(): void
    {
        $svc = $this->lessonService();
        $session = $svc->createAdhoc($this->teacher());
        $svc->addTask($session, 'alg-skill', $this->skillRefs(1));
        $svc->addTask($session, 'alg-skill', $this->skillRefs(2));
        $svc->start($session);

        $groups = $this->suggestionService()->suggestionsFor($session->fresh());

        $this->assertCount(1, $groups);
        $g = $groups[0];
        $this->assertSame(2, $g['lesson_stats']['task_count']);
        $this->assertFalse($g['no_analogs']);
        foreach ($g['suggestions'] as $s) {
            $this->assertNotContains((string) $s['refs']['task_id'], ['1', '2']);
        }
        // Уровень signed-add/simple = 20 задач, 2 на уроке → 18 аналогов.
        $this->assertCount(18, $g['suggestions']);
        $this->assertNotSame('', $g['suggestions'][0]['preview_text']);
    }

    public function test_group_without_remaining_analogs_flagged(): void
    {
        $svc = $this->lessonService();
        $session = $svc->createAdhoc($this->teacher());
        for ($id = 1; $id <= 20; $id++) {
            $svc->addTask($session, 'alg-skill', $this->skillRefs($id));
        }
        $svc->start($session);

        $groups = $this->suggestionService()->suggestionsFor($session->fresh());

        $this->assertCount(1, $groups);
        $this->assertTrue($groups[0]['no_analogs']);
        $this->assertSame([], $groups[0]['suggestions']);
    }

    public function test_personal_task_participates_in_grouping(): void
    {
        $svc = $this->lessonService();
        $session = $svc->createAdhoc($this->teacher());
        $student = $this->student();
        $svc->joinByCode($session->join_code, $student);
        $svc->addTask($session, 'alg-skill', $this->skillRefs(1));
        $svc->addTask($session, 'alg-skill', $this->skillRefs(2), $student->id);
        $svc->start($session);

        $groups = $this->suggestionService()->suggestionsFor($session->fresh());

        $this->assertCount(1, $groups);
        // Персональная задача учтена в группировке наравне с общей.
        $this->assertSame(2, $groups[0]['lesson_stats']['task_count']);
    }

    public function test_groups_without_analogs_go_last(): void
    {
        // Первой в окне стояла исчерпанная группа «аналогов нет», и учитель
        // решал, что выдавать нечего, не долистав до остальных.
        $svc = $this->lessonService();
        $session = $svc->createAdhoc($this->teacher());
        for ($id = 1; $id <= 20; $id++) {
            $svc->addTask($session, 'alg-skill', $this->skillRefs($id));
        }
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'medium', 'task_id' => 1]);
        $svc->start($session);

        $groups = $this->suggestionService()->suggestionsFor($session->fresh());

        $this->assertCount(2, $groups);
        $this->assertFalse($groups[0]['no_analogs']);
        $this->assertStringContainsString(':medium', $groups[0]['key']);
        $this->assertTrue($groups[1]['no_analogs']);
    }

    public function test_ege_suggestion_carries_condition_markup_with_drawing(): void
    {
        // У ЕГЭ числа векторов и графиков есть только на рисунке: без разметки
        // карточки аналогов в окне домашки не отличались друг от друга.
        TaskTopic::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '02',
            'payload' => ['topic_id' => '02', 'meta' => ['title' => 'Векторы']],
        ]);
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '02',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 2,
            'position' => 0, 'instruction' => 'Векторы', 'type' => 'fipi',
            'payload' => ['instruction' => 'Векторы', 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        foreach ([1, 2] as $id) {
            Task::create([
                'task_group_id' => $group->id, 'position' => $id - 1, 'type' => 'fipi',
                'payload' => ['id' => $id, 'status' => 'production', 'answer' => (string) $id,
                    'html' => "<p>Найдите длину вектора.<img class=\"fipi-figure\" src=\"/ege-bank/img/V/{$id}.png\" alt=\"рисунок\"></p>"],
                'answer' => (string) $id, 'answer_src' => 'codex', 'status' => 'production',
                'source' => 'fipi', 'fipi_guid' => str_pad((string) $id, 32, 'B'),
            ]);
        }
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        $svc = $this->lessonService();
        $session = $svc->createAdhoc($this->teacher());
        $svc->addTask($session, 'ege', ['topic_id' => '02', 'zadanie_number' => 2, 'task_id' => 1]);
        $svc->start($session);

        $groups = $this->suggestionService()->suggestionsFor($session->fresh());

        $this->assertCount(1, $groups[0]['suggestions']);
        $this->assertStringContainsString('/ege-bank/img/V/2.png', (string) $groups[0]['suggestions'][0]['preview_html']);
    }
}
