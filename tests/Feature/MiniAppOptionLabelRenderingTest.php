<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MiniAppOptionLabelRenderingTest extends TestCase
{
    public function test_tasks_part1_view_renders_option_labels_and_formats_choice_answer(): void
    {
        $view = $this->view('miniapp.tasks-part1', [
            'taskCount' => 1,
            'topicIds' => ['13'],
            'selectedTopic' => '13',
            'isPremium' => true,
            'trialUsed' => false,
            'zadaniya' => [[
                'title' => 'Подборка',
                'tasks' => [[
                    'text' => 'Выбери ответ',
                    'options' => [
                        ['id' => 'c', 'label' => 'x > 2'],
                        ['id' => 'a', 'label' => 'x < -1'],
                        ['id' => 'b', 'label' => 'x = 0'],
                    ],
                    'answer' => 'b',
                ]],
            ]],
        ]);

        $view->assertSeeText('В. x > 2');
        $view->assertSeeText('А. x < -1');
        $view->assertSeeText('Б. x = 0');
        $view->assertSeeText('Б');
    }

    public function test_history_detail_view_renders_option_labels_and_formatted_answers(): void
    {
        $attempt = new OgeAttempt([
            'submitted_at' => Carbon::parse('2026-03-21 10:00:00'),
        ]);

        $view = $this->view('miniapp.history-detail', [
            'label' => 'Мини-ОГЭ',
            'correct' => 0,
            'total' => 1,
            'time' => 90,
            'attempt' => $attempt,
            'wrongTasks' => [[
                'task_number' => 13,
                'task_instruction' => '',
                'task_text' => 'Выбери ответ',
                'task_expression' => '',
                'task_svg' => '',
                'task_image' => '',
                'task_options' => [
                    ['id' => 'd', 'label' => 'x > 5'],
                    ['id' => 'b', 'label' => 'x = 1'],
                ],
                'student_answer' => 'b',
                'correct_answer' => 'd',
            ]],
        ]);

        $view->assertSeeText('Г. x > 5');
        $view->assertSeeText('Б. x = 1');
        $view->assertSeeText('Твой ответ: Б');
        $view->assertSeeText('Верный: Г');
    }

    public function test_results_view_formats_choice_answers_using_stable_option_ids(): void
    {
        $attempt = new OgeAttempt([
            'id' => 42,
            'submitted_at' => Carbon::parse('2026-03-21 10:00:00'),
            'started_at' => Carbon::parse('2026-03-21 09:58:00'),
        ]);

        $attempt->setRelation('variant', new OgeVariant([
            'config_json' => [
                'tasks' => [[
                    'slot' => 1,
                    'exam_number' => 13,
                    'task_number' => 13,
                    'topic_id' => '13',
                    'block_number' => 1,
                    'zadanie_number' => 6,
                    'task' => [
                        'id' => 501,
                        'options' => [
                            ['id' => 'c', 'label' => 'x > 2'],
                            ['id' => 'a', 'label' => 'x < -1'],
                            ['id' => 'b', 'label' => 'x = 0'],
                        ],
                    ],
                ]],
            ],
        ]));

        $scorings = new Collection([
            new OgeAttemptScoring([
                'task_number' => 1,
                'correct_answer' => 'c',
                'is_correct' => false,
            ]),
        ]);

        $answers = collect([
            (object) ['task_number' => 1, 'current_answer' => 'b'],
        ]);

        $view = $this->view('miniapp.results', [
            'attempt' => $attempt,
            'scorings' => $scorings,
            'answers' => $answers,
            'totalTasks' => 1,
            'correctCount' => 0,
            'totalTime' => 120,
            'leaderboard' => [],
            'isBattleVariant' => false,
        ]);

        $view->assertSee('"yourAnswer":"\\u0411"', false);
        $view->assertSee('"correctAnswer":"\\u0412"', false);
    }
}
