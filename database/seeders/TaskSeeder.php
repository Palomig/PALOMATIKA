<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Topic;
use App\Models\Skill;
use App\Models\TaskStep;
use App\Models\StepBlock;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Get topics
        $topicEquations = Topic::where('oge_number', '08')->first();
        $topicGeometry = Topic::where('oge_number', '15')->first();
        $topicPractice = Topic::where('oge_number', '01-05')->first();

        // Get skills
        $skillQuadratic = Skill::where('slug', 'kvadratnye-uravneniia')->first();
        $skillPythagoras = Skill::where('slug', 'teorema-pifagora')->first();
        $skillFractions = Skill::where('slug', 'deiistviia-s-drobiami')->first();

        // Sample task 1: Quadratic equation
        if ($topicEquations) {
            $task1 = Task::create([
                'topic_id' => $topicEquations->id,
                'text' => 'Решите уравнение x² - 5x + 6 = 0',
                'text_html' => 'Решите уравнение <span class="katex">x^2 - 5x + 6 = 0</span>',
                'difficulty' => 2,
                'correct_answer' => '2;3',
                'answer_type' => 'text',
                'is_active' => true,
            ]);

            // Add puzzle steps
            $step1 = TaskStep::create([
                'task_id' => $task1->id,
                'step_number' => 1,
                'instruction' => 'Определите коэффициенты квадратного уравнения ax² + bx + c = 0',
                'template' => 'a = [___], b = [___], c = [___]',
                'correct_answers' => ['1', '-5', '6'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step1->id, 'content' => '1', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step1->id, 'content' => '-5', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step1->id, 'content' => '6', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 2],
                ['task_step_id' => $step1->id, 'content' => '5', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => 'Знак b отрицательный!', 'sort_order' => 3],
                ['task_step_id' => $step1->id, 'content' => '-6', 'is_correct' => false, 'is_trap' => true, 'sort_order' => 4],
            ]);

            $step2 = TaskStep::create([
                'task_id' => $task1->id,
                'step_number' => 2,
                'instruction' => 'Вычислите дискриминант D = b² - 4ac',
                'template' => 'D = (-5)² - 4·1·6 = [___] - [___] = [___]',
                'correct_answers' => ['25', '24', '1'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step2->id, 'content' => '25', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step2->id, 'content' => '24', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step2->id, 'content' => '1', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 2],
                ['task_step_id' => $step2->id, 'content' => '-25', 'is_correct' => false, 'is_trap' => true, 'sort_order' => 3],
                ['task_step_id' => $step2->id, 'content' => '49', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 4],
            ]);

            $step3 = TaskStep::create([
                'task_id' => $task1->id,
                'step_number' => 3,
                'instruction' => 'Найдите корни по формуле x = (-b ± √D) / 2a',
                'template' => 'x₁ = (5 + 1) / 2 = [___], x₂ = (5 - 1) / 2 = [___]',
                'correct_answers' => ['3', '2'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step3->id, 'content' => '3', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step3->id, 'content' => '2', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step3->id, 'content' => '4', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 2],
                ['task_step_id' => $step3->id, 'content' => '6', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 3],
            ]);

            // Attach skills
            if ($skillQuadratic) {
                $task1->skills()->attach($skillQuadratic->id, ['relevance' => 1.0]);
            }
        }

        // Sample task 2: Pythagoras theorem
        if ($topicGeometry) {
            $task2 = Task::create([
                'topic_id' => $topicGeometry->id,
                'text' => 'В прямоугольном треугольнике катеты равны 3 и 4. Найдите гипотенузу.',
                'difficulty' => 1,
                'correct_answer' => '5',
                'answer_type' => 'number',
                'is_active' => true,
            ]);

            $step1 = TaskStep::create([
                'task_id' => $task2->id,
                'step_number' => 1,
                'instruction' => 'Запишите теорему Пифагора',
                'template' => 'c² = [___] + [___]',
                'correct_answers' => ['a²', 'b²'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step1->id, 'content' => 'a²', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step1->id, 'content' => 'b²', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step1->id, 'content' => 'a + b', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => 'Нужны квадраты катетов!', 'sort_order' => 2],
                ['task_step_id' => $step1->id, 'content' => '2ab', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 3],
            ]);

            $step2 = TaskStep::create([
                'task_id' => $task2->id,
                'step_number' => 2,
                'instruction' => 'Подставьте значения и вычислите',
                'template' => 'c² = 3² + 4² = [___] + [___] = [___]',
                'correct_answers' => ['9', '16', '25'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step2->id, 'content' => '9', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step2->id, 'content' => '16', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step2->id, 'content' => '25', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 2],
                ['task_step_id' => $step2->id, 'content' => '12', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => '3² = 9, не 3×4', 'sort_order' => 3],
                ['task_step_id' => $step2->id, 'content' => '7', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => 'Это сумма катетов, нужны квадраты', 'sort_order' => 4],
            ]);

            $step3 = TaskStep::create([
                'task_id' => $task2->id,
                'step_number' => 3,
                'instruction' => 'Найдите c',
                'template' => 'c = √25 = [___]',
                'correct_answers' => ['5'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step3->id, 'content' => '5', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step3->id, 'content' => '25', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => 'Забыли извлечь корень!', 'sort_order' => 1],
                ['task_step_id' => $step3->id, 'content' => '12.5', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 2],
            ]);

            if ($skillPythagoras) {
                $task2->skills()->attach($skillPythagoras->id, ['relevance' => 1.0]);
            }
        }

        // Sample task 3: Simple calculation
        if ($topicPractice) {
            $task3 = Task::create([
                'topic_id' => $topicPractice->id,
                'text' => 'Вычислите: 2/3 + 1/4',
                'difficulty' => 1,
                'correct_answer' => '11/12',
                'answer_type' => 'text',
                'is_active' => true,
            ]);

            $step1 = TaskStep::create([
                'task_id' => $task3->id,
                'step_number' => 1,
                'instruction' => 'Найдите общий знаменатель',
                'template' => 'НОК(3, 4) = [___]',
                'correct_answers' => ['12'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step1->id, 'content' => '12', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step1->id, 'content' => '7', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => '7 = 3+4, но нам нужен НОК', 'sort_order' => 1],
                ['task_step_id' => $step1->id, 'content' => '24', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 2],
            ]);

            $step2 = TaskStep::create([
                'task_id' => $task3->id,
                'step_number' => 2,
                'instruction' => 'Приведите дроби к общему знаменателю',
                'template' => '2/3 = [___]/12, 1/4 = [___]/12',
                'correct_answers' => ['8', '3'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step2->id, 'content' => '8', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step2->id, 'content' => '3', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 1],
                ['task_step_id' => $step2->id, 'content' => '6', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 2],
                ['task_step_id' => $step2->id, 'content' => '4', 'is_correct' => false, 'is_trap' => false, 'sort_order' => 3],
            ]);

            $step3 = TaskStep::create([
                'task_id' => $task3->id,
                'step_number' => 3,
                'instruction' => 'Сложите дроби',
                'template' => '8/12 + 3/12 = [___]/12',
                'correct_answers' => ['11'],
            ]);

            StepBlock::insert([
                ['task_step_id' => $step3->id, 'content' => '11', 'is_correct' => true, 'is_trap' => false, 'sort_order' => 0],
                ['task_step_id' => $step3->id, 'content' => '24', 'is_correct' => false, 'is_trap' => true, 'trap_explanation' => 'Знаменатели не складываются!', 'sort_order' => 1],
            ]);

            if ($skillFractions) {
                $task3->skills()->attach($skillFractions->id, ['relevance' => 1.0]);
            }
        }
    }
}
