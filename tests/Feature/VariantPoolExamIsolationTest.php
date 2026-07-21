<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\User;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use App\Support\StudentSolvedTasks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Пул вариантов выпилен: каждый старт генерирует свежий рандомный вариант,
 * в пул ничего не пишется, старые пул-записи не переиспользуются.
 */
class VariantPoolExamIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    private function makeVariant(string $hash, string $examType, string $mode = OgeVariant::MODE_FULL): OgeVariant
    {
        return OgeVariant::create([
            'hash' => $hash,
            'exam_type' => $examType,
            'title' => "Variant {$examType}",
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => $mode,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 1, 'topic_id' => '01', 'text' => 'Task 1'],
                ],
            ],
        ]);
    }

    private function addPoolEntry(OgeVariant $variant, string $type): void
    {
        OgeVariantPoolEntry::create([
            'variant_id' => $variant->id,
            'exam_type' => $variant->exam_type,
            'type' => $type,
            'status' => 'active',
            'task_fingerprint' => md5($variant->hash . '|' . $type),
            'created_at' => now(),
        ]);
    }

    private function ogeService(): OgeVariantPoolService
    {
        return new OgeVariantPoolService(
            app(TaskDataService::class),
            app(MiniAppTaskCanonicalizer::class),
        );
    }

    public function test_oge_generates_fresh_variant_and_ignores_pool(): void
    {
        $student = $this->makeStudent(9);
        // Живая пул-запись подходящего типа — раньше вернулась бы она.
        $pooled = $this->makeVariant('ogemixed1', OgeVariant::EXAM_OGE, OgeVariant::MODE_MINI_MIXED);
        $this->addPoolEntry($pooled, 'mixed');
        $poolCountBefore = OgeVariantPoolEntry::count();

        $service = $this->ogeService();
        $first = $service->getOrCreateVariant($student, 'mixed');
        $second = $service->getOrCreateVariant($student, 'mixed');

        $this->assertSame(OgeVariant::EXAM_OGE, $first->exam_type);
        $this->assertNotSame($pooled->id, $first->id);
        // Каждый старт — новый вариант.
        $this->assertNotSame($first->id, $second->id);
        // В пул больше не пишем.
        $this->assertSame($poolCountBefore, OgeVariantPoolEntry::count());
    }

    public function test_oge_full_generation_excludes_tasks_from_past_attempts(): void
    {
        $student = $this->makeStudent(9);
        $service = $this->ogeService();

        // Первый полный вариант — запоминаем задачи через попытку.
        $first = $service->getOrCreateVariant($student, 'full');
        OgeAttempt::create([
            'variant_id' => $first->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now(),
        ]);

        $solved = StudentSolvedTasks::mapByTopic($student, OgeVariant::EXAM_OGE);
        $this->assertNotEmpty($solved);

        // Второй вариант не должен повторить ни один решённый пример
        // (банк каждой темы больше одного примера).
        $second = $service->getOrCreateVariant($student, 'full');
        foreach ($second->config_json['tasks'] as $task) {
            $topicId = str_pad((string) $task['topic_id'], 2, '0', STR_PAD_LEFT);
            $taskId = (int) ($task['task']['id'] ?? $task['task_id'] ?? 0);
            if ($taskId > 0 && isset($solved[$topicId])) {
                $this->assertNotContains(
                    $taskId,
                    $solved[$topicId],
                    "Тема {$topicId}: задача {$taskId} повторилась, хотя уже решена"
                );
            }
        }
    }

    public function test_vpr_generates_fresh_variant_even_when_pool_has_matching_grade(): void
    {
        $student = $this->makeStudent(6);
        $gradeSix = $this->makeVariant('vpr6full1', OgeVariant::EXAM_VPR6, OgeVariant::MODE_FULL);
        $this->addPoolEntry($gradeSix, 'full');
        $poolCountBefore = OgeVariantPoolEntry::count();

        $service = new VprVariantPoolService(
            new VprTaskDataService(6),
            new VprVariantBuilderService(new VprTaskDataService(6)),
        );

        $picked = $service->getOrCreateVariant($student, 'full');

        $this->assertSame(OgeVariant::EXAM_VPR6, $picked->exam_type);
        $this->assertNotSame($gradeSix->id, $picked->id);
        $this->assertSame($poolCountBefore, OgeVariantPoolEntry::count());
    }
}
