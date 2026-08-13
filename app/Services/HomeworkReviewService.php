<?php

namespace App\Services;

use App\Models\HomeworkReviewItem;
use App\Models\LessonSession;
use Illuminate\Support\Collection;

/**
 * Сборка карточек разбора — вторая стадия домашки.
 *
 * Один источник для трёх экранов (подготовка урока, урок учителя, урок
 * ученика), чтобы карточка не собиралась в каждом по-своему. Задачи разбора
 * намеренно НЕ попадают в lesson_session_tasks: там у каждой строки есть
 * bank+refs и поле ответа, а разбор — это «смотрим на то, что уже написано».
 */
class HomeworkReviewService
{
    public const VIEWER_TEACHER = 'teacher';
    public const VIEWER_STUDENT = 'student';

    /**
     * Живые пункты учеников — то, что можно добавить в урок.
     *
     * @param array<int, int> $studentIds
     * @return array<int, array>
     */
    public function pendingFor(array $studentIds, int $teacherId): array
    {
        if ($studentIds === []) {
            return [];
        }

        $items = $this->query()
            ->whereIn('student_id', $studentIds)
            ->where('teacher_id', $teacherId)
            ->where('status', HomeworkReviewItem::STATUS_PENDING)
            ->get();

        return $this->cards($items, self::VIEWER_TEACHER);
    }

    /**
     * Пункты, уже поставленные в повестку этого урока.
     *
     * @return array<int, array>
     */
    public function plannedFor(LessonSession $session): array
    {
        $items = $this->query()
            ->where('lesson_session_id', $session->id)
            ->where('status', HomeworkReviewItem::STATUS_PLANNED)
            ->get();

        return $this->cards($items, self::VIEWER_TEACHER);
    }

    /**
     * Карточки для экрана ученика: только его собственные и только из этого
     * урока. Заметку учителя ученику не отдаём — она писалась не для него.
     *
     * @return array<int, array>
     */
    public function cardsForStudent(LessonSession $session, int $studentId): array
    {
        $items = $this->query()
            ->where('lesson_session_id', $session->id)
            ->where('student_id', $studentId)
            ->where('status', HomeworkReviewItem::STATUS_PLANNED)
            ->get();

        return $this->cards($items, self::VIEWER_STUDENT);
    }

    /**
     * pending → planned с привязкой к уроку. Возвращает число переведённых.
     *
     * @param array<int, int> $itemIds
     */
    public function planInto(LessonSession $session, array $itemIds): int
    {
        $studentIds = $session->participants()->pluck('student_id')->all();
        if ($itemIds === [] || $studentIds === []) {
            return 0;
        }

        // Пункт уезжает в урок, только если его ученик — участник этого урока.
        return HomeworkReviewItem::whereIn('id', $itemIds)
            ->whereIn('student_id', $studentIds)
            ->where('teacher_id', $session->teacher_id)
            ->where('status', HomeworkReviewItem::STATUS_PENDING)
            ->update([
                'status' => HomeworkReviewItem::STATUS_PLANNED,
                'lesson_session_id' => $session->id,
            ]);
    }

    /** Учитель передумал: пункт возвращается в общую очередь. */
    public function unplan(LessonSession $session, int $itemId): void
    {
        HomeworkReviewItem::where('id', $itemId)
            ->where('lesson_session_id', $session->id)
            ->where('status', HomeworkReviewItem::STATUS_PLANNED)
            ->update([
                'status' => HomeworkReviewItem::STATUS_PENDING,
                'lesson_session_id' => null,
            ]);
    }

    /**
     * Всё, что стояло в повестке урока, гаснет вместе с уроком: держать это
     * на учителе — значит копить мусор. Возвращает число погашенных.
     */
    public function resolveForSession(LessonSession $session): int
    {
        return HomeworkReviewItem::where('lesson_session_id', $session->id)
            ->where('status', HomeworkReviewItem::STATUS_PLANNED)
            ->update([
                'status' => HomeworkReviewItem::STATUS_DONE,
                'resolved_at' => now(),
            ]);
    }

    private function query()
    {
        return HomeworkReviewItem::with([
            'student:id,name',
            'topicTask',
            'assignment.topicTaskSubmissions.photos',
        ])->orderBy('student_id')->orderBy('id');
    }

    /**
     * @param Collection<int, HomeworkReviewItem> $items
     * @return array<int, array>
     */
    private function cards(Collection $items, string $viewer): array
    {
        return $items->map(function (HomeworkReviewItem $item) use ($viewer) {
            $task = $item->topicTask;
            $payload = $task?->task_payload ?? [];

            $submission = $item->assignment?->topicTaskSubmissions
                ->firstWhere('homework_topic_task_id', $item->homework_topic_task_id);

            $card = [
                'id' => (int) $item->id,
                'student_id' => (int) $item->student_id,
                'student_name' => $item->student?->name,
                'task_order' => (int) ($task?->task_order ?? 0),
                'text' => $this->taskText($payload),
                'svg' => $this->taskSvg($payload),
                'correct' => (string) ($task?->correct_answer ?? ''),
                'first_answer' => $submission?->first_answer,
                'second_answer' => $submission?->second_answer,
                'photos' => $this->photos($submission, $viewer),
            ];

            if ($viewer === self::VIEWER_TEACHER) {
                $card['teacher_note'] = $item->note;
                $card['homework_url'] = route('pwa.teacher.homework.submissions', $item->homework_assignment_id);
            }

            return $card;
        })->values()->all();
    }

    /** Тот же порядок ключей, что и на странице проверки домашки. */
    private function taskText(array $payload): string
    {
        return (string) ($payload['text_html']
            ?? $payload['text']
            ?? $payload['html']
            ?? $payload['question']
            ?? $payload['expression']
            ?? 'Задача');
    }

    private function taskSvg(array $payload): ?string
    {
        $svg = $payload['svg'] ?? null;

        return is_string($svg) && str_contains($svg, '<svg') ? $svg : null;
    }

    /**
     * Маршрут к фото зависит от того, кому отдаём: у учителя и ученика они
     * разные и с разными проверками доступа. Режим передаётся явно — гадать
     * по auth() здесь нельзя, карточки собираются и для чужого экрана.
     *
     * @return array<int, array{url:string,full:string,label:string}>
     */
    private function photos(?object $submission, string $viewer): array
    {
        if ($submission === null) {
            return [];
        }

        $routeName = $viewer === self::VIEWER_STUDENT
            ? 'pwa.student.homework.solution-photo'
            : 'pwa.teacher.homework.solution-photo';

        $out = [];
        foreach ($submission->photos->groupBy('attempt_no') as $attemptNo => $pages) {
            foreach ($pages->values() as $i => $photo) {
                $out[] = [
                    'url' => route($routeName, [$photo->id, 'w' => 800]),
                    'full' => route($routeName, $photo->id),
                    'label' => ((int) $attemptNo === 2 ? 'вторая попытка' : 'первая попытка')
                        . ' · стр. ' . ($i + 1),
                ];
            }
        }

        return $out;
    }
}
