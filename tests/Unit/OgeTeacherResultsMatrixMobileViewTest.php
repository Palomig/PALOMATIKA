<?php

namespace Tests\Unit;

use App\Http\Controllers\Teacher\OgeReviewController;
use App\Models\OgeVariant;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class OgeTeacherResultsMatrixMobileViewTest extends TestCase
{
    public function test_results_view_renders_mobile_matrix_cards_branch_with_task_chips(): void
    {
        $variant = new OgeVariant([
            'hash' => 'abc123',
            'owner_teacher_id' => 7,
            'source' => OgeVariant::SOURCE_CUSTOM_RANDOM,
        ]);
        $variant->setRelation('ownerTeacher', (object) ['name' => 'Teacher QA']);

        $html = view('teacher.oge.results', [
            'variant' => $variant,
            'attempts' => collect(),
            'taskColumns' => [],
            'resultsMatrix' => [
                'students' => [
                    [
                        'attempt_id' => 11,
                        'student_name' => 'Иван Петров',
                        'student_short_name' => 'ИвП',
                        'status' => 'submitted',
                    ],
                    [
                        'attempt_id' => 12,
                        'student_name' => 'Анна Соколова',
                        'student_short_name' => 'АнС',
                        'status' => 'draft',
                    ],
                ],
                'rows' => [
                    [
                        'task_number' => 6,
                        'cells' => [
                            ['is_correct' => true, 'mark' => '+'],
                            ['is_correct' => false, 'mark' => '-'],
                        ],
                    ],
                    [
                        'task_number' => 15,
                        'cells' => [
                            ['is_correct' => null, 'mark' => '.'],
                            ['is_correct' => true, 'mark' => '+'],
                        ],
                    ],
                    [
                        'task_number' => 17,
                        'cells' => [
                            ['is_correct' => false, 'mark' => '-'],
                            ['is_correct' => null, 'mark' => '.'],
                        ],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('data-mobile-results-matrix', $html);
        $this->assertStringContainsString('data-mobile-student-card="11"', $html);
        $this->assertStringContainsString('data-mobile-student-card="12"', $html);
        $this->assertStringContainsString('data-mobile-task-chip="11-6"', $html);
        $this->assertStringContainsString('data-mobile-task-chip="11-15"', $html);
        $this->assertStringContainsString('data-mobile-task-chip="12-17"', $html);
        $this->assertStringContainsString('ИвП', $html);
        $this->assertStringContainsString('АнС', $html);
    }

    public function test_results_matrix_mapping_keeps_custom_display_task_numbers_and_marks(): void
    {
        $controller = new OgeReviewController();
        $resolveTaskColumns = new ReflectionMethod($controller, 'resolveTaskColumns');
        $resolveTaskColumns->setAccessible(true);
        $buildResultsMatrix = new ReflectionMethod($controller, 'buildResultsMatrix');
        $buildResultsMatrix->setAccessible(true);

        $variant = new OgeVariant([
            'source' => OgeVariant::SOURCE_CUSTOM_RANDOM,
            'config_json' => [
                'custom_tasks' => [
                    ['zadanie_number' => 6, 'attempt_task_number' => 1],
                    ['zadanie_number' => 8, 'attempt_task_number' => 2],
                    ['zadanie_number' => 9, 'attempt_task_number' => 3],
                    ['zadanie_number' => 15, 'attempt_task_number' => 4],
                    ['zadanie_number' => 17, 'attempt_task_number' => 5],
                ],
            ],
        ]);

        $attempts = collect([
            $this->makeAttempt(101, 'Иван Петров', [
                1 => true,
                2 => false,
                3 => null,
                4 => true,
                5 => false,
            ]),
            $this->makeAttempt(102, 'Анна Соколова', [
                1 => false,
                2 => true,
                3 => true,
                4 => null,
                5 => true,
            ]),
        ]);

        $taskColumns = $resolveTaskColumns->invoke($controller, $variant, $attempts);
        $matrix = $buildResultsMatrix->invoke($controller, $attempts, $taskColumns);

        $this->assertSame([6, 8, 9, 15, 17], array_column($taskColumns, 'display_task_number'));
        $this->assertSame([6, 8, 9, 15, 17], array_column($matrix['rows'], 'task_number'));
        $this->assertSame(['+', '-'], array_column($matrix['rows'][0]['cells'], 'mark'));
        $this->assertSame(['-', '+'], array_column($matrix['rows'][1]['cells'], 'mark'));
        $this->assertSame(['.', '+'], array_column($matrix['rows'][2]['cells'], 'mark'));
        $this->assertSame(['+', '.'], array_column($matrix['rows'][3]['cells'], 'mark'));
        $this->assertSame(['-', '+'], array_column($matrix['rows'][4]['cells'], 'mark'));
    }

    /**
     * @param array<int, bool|null> $scoresByTask
     */
    private function makeAttempt(int $id, string $studentName, array $scoresByTask): object
    {
        $scorings = collect();

        foreach ($scoresByTask as $taskNumber => $isCorrect) {
            $scorings->push((object) [
                'task_number' => $taskNumber,
                'is_correct' => $isCorrect,
            ]);
        }

        return (object) [
            'id' => $id,
            'status' => 'submitted',
            'student' => (object) ['name' => $studentName, 'email' => null],
            'answers' => collect(),
            'taskTimings' => collect(),
            'scorings' => $scorings,
        ];
    }
}
