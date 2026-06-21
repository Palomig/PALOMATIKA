<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use Tests\TestCase;

class LessonPickerPreviewTest extends TestCase
{
    public function test_grade7_skills_carry_preview(): void
    {
        $picker = new LessonTaskPickerService();
        $skills = $picker->skills(7);

        $this->assertNotEmpty($skills, '7 класс не отдал ни одного навыка');
        foreach ($skills as $s) {
            $this->assertArrayHasKey('preview', $s);
            $hasPreview = ($s['preview'] ?? '') !== '' || ($s['preview_svg'] ?? '') !== '';
            $this->assertTrue($hasPreview, "Навык {$s['slug']} без примера");
        }
    }
}
