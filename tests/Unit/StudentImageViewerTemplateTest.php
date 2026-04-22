<?php

namespace Tests\Unit;

use Tests\TestCase;

class StudentImageViewerTemplateTest extends TestCase
{
    public function test_student_test_templates_include_shared_image_viewer_partial(): void
    {
        $templates = [
            resource_path('views/pwa/student/test.blade.php'),
            resource_path('views/pwa/student/ege-test.blade.php'),
            resource_path('views/pwa/student/vpr-test.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = (string) file_get_contents($template);

            $this->assertStringContainsString(
                "@include('pwa.student.partials.image-viewer')",
                $contents,
                $template.' should include the shared image viewer partial.'
            );
        }
    }
}
