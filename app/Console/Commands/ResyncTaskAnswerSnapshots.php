<?php

namespace App\Console\Commands;

use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\LessonSessionAttempt;
use App\Models\LessonSessionTask;
use App\Models\Task;
use App\Services\TaskAnswerResolver;
use Illuminate\Console\Command;

/**
 * Подтянуть исправленный эталон банка в уже розданные копии задачи.
 *
 * Домашка и урок хранят задачу СНИМКОМ вместе с ответом — иначе замена
 * банка ломала бы старые работы. Обратная сторона: правка ответа в банке
 * ({@see FixTaskAnswer}) старые работы не догоняет, и ученик остаётся с
 * «неверно» за верный ответ. Команда проходит по снимкам той же задачи
 * (ищем по `fipi_guid` внутри payload) и пересчитывает проверку.
 *
 * Пересчёт делает тот же {@see TaskAnswerResolver}, что и приём ответа;
 * оценка берётся по последней попытке ученика — как при отправке.
 *
 *   php artisan tasks:resync-answer --guid=efb16f1c… --dry-run
 *   php artisan tasks:resync-answer --guid=efb16f1c…
 */
class ResyncTaskAnswerSnapshots extends Command
{
    protected $signature = 'tasks:resync-answer
        {--guid= : fipi_guid задачи банка}
        {--dry-run : показать, что изменится, и не писать}';

    protected $description = 'Обновить эталон в снимках задачи (домашки, уроки) и пересчитать проверку';

    public function handle(TaskAnswerResolver $resolver): int
    {
        $guid = trim((string) $this->option('guid'));
        if ($guid === '') {
            $this->error('Нужен --guid');
            return 1;
        }

        $task = Task::where('fipi_guid', $guid)->first();
        if (!$task) {
            $this->error("Задача с guid {$guid} не найдена в банке");
            return 1;
        }

        $answer = (string) $task->answer;
        if ($answer === '') {
            $this->error('У задачи банка пустой ответ — нечего разносить');
            return 1;
        }

        $dry = (bool) $this->option('dry-run');
        $this->line("Эталон банка: {$answer}");

        $this->homework($guid, $answer, $resolver, $dry);
        $this->lessons($guid, $answer, $resolver, $dry);

        if ($dry) {
            $this->warn('--dry-run: в базу не писали');
        }

        return 0;
    }

    private function homework(string $guid, string $answer, TaskAnswerResolver $resolver, bool $dry): void
    {
        $tasks = HomeworkTopicTask::query()
            ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(task_payload, "$.fipi_guid")) = ?', [$guid])
            ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(task_payload, "$.raw.fipi_guid")) = ?', [$guid])
            ->get();

        foreach ($tasks as $hwTask) {
            $this->line(sprintf(
                'Домашка %d · задача %d: эталон %s → %s',
                $hwTask->homework_id, $hwTask->task_order, $hwTask->correct_answer, $answer
            ));

            if (!$dry) {
                $hwTask->correct_answer = $answer;
                $hwTask->task_payload = $this->withAnswer($hwTask->task_payload ?? [], $answer);
                $hwTask->save();
            }

            $submissions = HomeworkTopicTaskSubmission::where('homework_topic_task_id', $hwTask->id)->get();
            foreach ($submissions as $sub) {
                // Как при отправке: считается последняя попытка ученика.
                $last = $sub->second_answer ?? $sub->first_answer;
                if ($last === null) {
                    continue;
                }
                $isCorrect = $resolver->isCorrect($last, $answer) === true;
                $this->line(sprintf(
                    '  ответ «%s»: %s → %s',
                    $last, $sub->is_correct ? 'верно' : 'неверно', $isCorrect ? 'верно' : 'неверно'
                ));

                if ($dry || $isCorrect === (bool) $sub->is_correct) {
                    continue;
                }

                $sub->is_correct = $isCorrect;
                // Верный ответ закрывает задачу, как и при обычной отправке.
                if ($isCorrect && $sub->accepted_at === null) {
                    $sub->accepted_at = now();
                }
                $sub->save();

                $this->refreshProgress($sub->homework_assignment_id);
            }
        }

        if ($tasks->isEmpty()) {
            $this->line('Домашки с этой задачей не найдены');
        }
    }

    private function lessons(string $guid, string $answer, TaskAnswerResolver $resolver, bool $dry): void
    {
        $tasks = LessonSessionTask::query()
            ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(task_payload, "$.raw.fipi_guid")) = ?', [$guid])
            ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(task_payload, "$.fipi_guid")) = ?', [$guid])
            ->get();

        foreach ($tasks as $lessonTask) {
            $this->line(sprintf(
                'Урок %d · позиция %d: эталон %s → %s',
                $lessonTask->lesson_session_id, $lessonTask->position, $lessonTask->correct_answer, $answer
            ));

            if (!$dry) {
                $lessonTask->correct_answer = $answer;
                $lessonTask->task_payload = $this->withAnswer($lessonTask->task_payload ?? [], $answer);
                $lessonTask->save();
            }

            foreach (LessonSessionAttempt::where('lesson_session_task_id', $lessonTask->id)->get() as $attempt) {
                $isCorrect = $resolver->isCorrect($attempt->answer_raw, $answer) === true;
                $this->line(sprintf(
                    '  ответ «%s»: %s → %s',
                    $attempt->answer_raw, $attempt->is_correct ? 'верно' : 'неверно', $isCorrect ? 'верно' : 'неверно'
                ));

                if (!$dry && $isCorrect !== (bool) $attempt->is_correct) {
                    $attempt->is_correct = $isCorrect;
                    $attempt->save();
                }
            }
        }

        if ($tasks->isEmpty()) {
            $this->line('Уроков с этой задачей не найдено');
        }
    }

    /** Ответ лежит и в снимке — интерфейс читает задачу из payload. */
    private function withAnswer(array $payload, string $answer): array
    {
        $payload['answer'] = $answer;
        if (isset($payload['raw']) && is_array($payload['raw'])) {
            $payload['raw']['answer'] = $answer;
        }
        return $payload;
    }

    /**
     * Счётчики работы — тот же расчёт, что в StudentController после
     * приёма ответа: закрытыми считаются задачи с accepted_at.
     */
    private function refreshProgress(int $assignmentId): void
    {
        $assignment = HomeworkAssignment::find($assignmentId);
        if (!$assignment) {
            return;
        }

        $accepted = HomeworkTopicTaskSubmission::where('homework_assignment_id', $assignment->id)
            ->whereNotNull('accepted_at')->get();

        $assignment->update([
            'tasks_completed' => $accepted->count(),
            'tasks_correct'   => $accepted->where('is_correct', true)->count(),
        ]);
    }
}
