<?php

namespace Tests\Unit;

use App\Services\LessonTaskPickerService;
use Tests\TestCase;

/**
 * Тесты используют реальные JSON-файлы банка ОГЭ (storage/app/tasks/topic_XX.json).
 * Инварианты данных: темы 06–21, 23–25 существуют; в теме 09 есть задание
 * с label 'Новые задания' (zadanie number 0, word_problem); тема 20 — word_problem с text.
 */
class LessonTaskPickerServiceTest extends TestCase
{
    private LessonTaskPickerService $picker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->picker = new LessonTaskPickerService();
    }

    public function test_sections_for_oge(): void
    {
        $sections = $this->picker->sections('oge');
        $this->assertSame(['part1', 'part2', 'new'], array_column($sections, 'id'));
        foreach ($sections as $s) {
            $this->assertNotSame('', $s['title']);
        }
    }

    public function test_sections_empty_for_other_banks(): void
    {
        $this->assertSame([], $this->picker->sections('ege'));
        $this->assertSame([], $this->picker->sections('alg-skill'));
    }

    public function test_part1_topics_are_06_to_19(): void
    {
        $ids = array_column($this->picker->topics('oge', null, 'part1'), 'id');
        $this->assertContains('06', $ids);
        $this->assertContains('19', $ids);
        $this->assertNotContains('20', $ids);
    }

    public function test_part2_topics(): void
    {
        $ids = array_column($this->picker->topics('oge', null, 'part2'), 'id');
        $this->assertSame(['20', '21', '23', '24', '25'], $ids);
    }

    public function test_new_section_topics(): void
    {
        $ids = array_column($this->picker->topics('oge', null, 'new'), 'id');
        $this->assertSame(['09', '10', '15', '16', '17'], $ids);
    }

    public function test_new_section_returns_only_new_zadaniya_tasks(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '09'], 'new');
        $this->assertNotEmpty($tasks);
        // Все задачи — из задания с label 'Новые задания'
        foreach ($tasks as $t) {
            $this->assertSame('new', $t['section'] ?? 'new');
        }
    }

    public function test_part1_tasks_exclude_new_zadaniya(): void
    {
        $all  = $this->picker->tasks('oge', ['topic_id' => '09'], 'part1');
        $new  = $this->picker->tasks('oge', ['topic_id' => '09'], 'new');
        $this->assertNotEmpty($all);
        $this->assertNotEmpty($new);
        $newUids = array_column($new, 'uid');
        foreach ($all as $t) {
            $this->assertNotContains($t['uid'], $newUids);
        }
    }

    public function test_part2_tasks_have_text_expression(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '20'], 'part2');
        $this->assertNotEmpty($tasks);
        $this->assertNotSame('', $tasks[0]['expression']);
        $this->assertArrayHasKey('group_label', $tasks[0]);
        $this->assertArrayHasKey('text', $tasks[0]);
        $this->assertArrayHasKey('image', $tasks[0]);
    }

    public function test_section_null_keeps_legacy_behaviour(): void
    {
        // Без section — как раньше (все темы ОГЭ)
        $ids = array_column($this->picker->topics('oge'), 'id');
        $this->assertGreaterThan(14, count($ids));

        // И задачи темы без section — без «новых заданий» (zadanie number 0)
        $tasks = $this->picker->tasks('oge', ['topic_id' => '09']);
        $this->assertNotEmpty($tasks);
        foreach ($tasks as $t) {
            $this->assertGreaterThan(0, $t['zadanie_number']);
        }
    }
}
