<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use App\Services\TaskBankResolver;
use Tests\TestCase;

class HomeworkPickerResolveTest extends TestCase
{
    /**
     * Контракт: refs, собранные фронтовым taskRefs() для alg-skill
     * (bank='alg-skill', refs={grade, skill_slug, level_id, task_id}),
     * резолвятся TaskBankResolver в непустые expression и answer.
     */
    public function test_alg_skill_picker_refs_resolve_to_payload(): void
    {
        $picker = new LessonTaskPickerService();
        $resolver = new TaskBankResolver();

        $skills = $picker->skills(7);
        $this->assertNotEmpty($skills, '7 класс не отдал ни одного навыка');
        $skillSlug = $skills[0]['slug'];

        $tasks = $picker->tasks('alg-skill', ['grade' => 7, 'skill_slug' => $skillSlug]);
        $this->assertNotEmpty($tasks, "Навык {$skillSlug} не отдал ни одной задачи");
        $first = $tasks[0];

        // refs ровно как их собирает фронтовый taskRefs() в task-picker.blade.php
        $refs = [
            'grade'      => 7,
            'skill_slug' => $skillSlug,
            'level_id'   => $first['level_id'],
            'task_id'    => $first['id'],
        ];

        $resolved = $resolver->resolve('alg-skill', $refs);

        $this->assertNotSame('', (string) $resolved['expression'], 'expression пуст');
        $this->assertNotSame('', (string) $resolved['answer'], 'answer пуст');
    }
}
