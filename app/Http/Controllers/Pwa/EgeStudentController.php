<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pwa\Concerns\NormalizesTaskImageViewer;
use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\OgeAttempt;
use App\Models\TeacherStudent;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use App\Services\EgeVariantPoolService;
use App\Services\OgeAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EgeStudentController extends Controller
{
    use MiniAppHelpers;
    use NormalizesTaskImageViewer;

    private function makePool(): EgeVariantPoolService
    {
        $taskData = app(EgeTaskDataService::class);
        $builder  = new EgeVariantBuilderService($taskData);
        return new EgeVariantPoolService($taskData, $builder);
    }

    public function home(Request $request)
    {
        $user  = Auth::user();
        // Учитель в режиме просмотра видел «9 класс» — значение по умолчанию
        // ОГЭ. Для ЕГЭ это 10–11, и подпись на экране должна совпадать с
        // экзаменом, иначе она сбивает с толку первой же строкой.
        $grade = $this->supportsStudentViewContext($request, $user)
            ? 11
            : (int) ($user->grade_num ?? 11);
        $gradeLabel = in_array($grade, [10, 11], true) ? (string) $grade : '10–11';

        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->whereHas('variant', fn($q) => $q->where('exam_type', 'ege'))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeList = $activeAttempts->map(function ($att) {
            $v = $att->variant;
            return [
                'id'            => $att->id,
                'title'         => $v->title ?? 'Вариант ЕГЭ',
                'answeredCount' => $att->answers()->count(),
                'totalCount'    => count($v->config_json['tasks'] ?? []),
                'startedAt'     => $att->started_at,
            ];
        })->all();

        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();
        // Урок и домашка — единый инструмент для всех классов, не только для ОГЭ.
        $showLessonTile = $hasTeacher || $user->isAdmin();

        // Последний номер задания — по самому банку, а не константой: на
        // экране стояло «20 заданий», хотя в профиле их 19. Берём именно
        // максимальный номер, а не количество тем: пока банк наполняется,
        // какой-то номер может временно отсутствовать, и «1–17» соврало бы
        // про сам экзамен.
        $topics = array_keys((new EgeTaskDataService())->getAvailableTopics());
        $taskCount = $topics ? max(array_map('intval', $topics)) : 19;

        // Слабые темы по ЕГЭ пока не считаются: экран показывает блок только
        // при непустом списке, как и ВПР до появления там статистики.
        $weakTopics = [];

        return view('pwa.student.ege-home', compact(
            'user', 'grade', 'gradeLabel', 'taskCount', 'activeList',
            'hasTeacher', 'showLessonTile', 'weakTopics'
        ));
    }

    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $user = Auth::user();
        if (
            !$this->supportsStudentViewContext($request, $user)
            && ($user->grade_num < 10 || $user->grade_num > 11)
        ) {
            abort(403, 'ЕГЭ доступно только для 10–11 классов');
        }

        $variant = $this->makePool()->getOrCreateVariant($user);
        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        return redirect()->route('pwa.student.ege.test', $attempt->id);
    }

    /**
     * База заданий ЕГЭ — внутренний раздел приложения.
     *
     * Плитка на домашнем экране раньше уводила на сайт (`/ege`), то есть из
     * приложения наружу. У ОГЭ и ВПР это экран внутри PWA, здесь — такой же:
     * пилюли номеров заданий, задачи со свёрнутыми группами и ответами.
     */
    public function taskDatabase(Request $request)
    {
        $user = Auth::user();
        $taskData = new EgeTaskDataService();

        $topicIds = collect(array_keys($taskData->getAllTopicsMeta()))
            ->map(fn ($topicId) => str_pad((string) $topicId, 2, '0', STR_PAD_LEFT))
            ->filter(fn (string $topicId) => $taskData->topicDataExists($topicId))
            ->values()
            ->all();

        if ($topicIds === []) {
            $topicIds = ['01'];
        }

        $maxTopic = max(array_map('intval', $topicIds));

        $selected = str_pad((string) $request->query('topic', '1'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topicIds, true)) {
            $selected = $topicIds[0];
        }

        $zadaniya = [];
        foreach ($taskData->getBlocks($selected) as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $task) {
                    // Черновики (без ответа или с непроверяемым «Да») ученику
                    // не показываем — как и в варианте.
                    if (($task['status'] ?? 'production') !== 'production') {
                        continue;
                    }
                    $html = trim((string) ($task['html'] ?? ''));
                    $text = trim((string) ($task['text'] ?? ''));
                    if ($html === '' && $text === '' && empty($task['svg']) && empty($task['image'])) {
                        continue;
                    }
                    $tasks[] = [
                        'id' => $task['id'] ?? null,
                        'html' => $html !== '' ? $html : null,
                        'text' => $text,
                        'expression' => $task['expression'] ?? null,
                        'svg' => $task['svg'] ?? null,
                        'image' => $task['image'] ?? null,
                        'options' => $task['options'] ?? null,
                        'question' => $task['question'] ?? null,
                        'answer' => $task['answer'] ?? null,
                    ];
                }
                if ($tasks !== []) {
                    $number = $zadanie['number'] ?? '';
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $zadaniya[] = [
                        'title' => $instruction !== '' ? $instruction : "Задание {$number}",
                        'tasks' => $tasks,
                    ];
                }
            }
        }

        $taskCount = array_sum(array_map(fn ($group) => count($group['tasks']), $zadaniya));

        return view('pwa.student.ege-tasks', compact(
            'user', 'topicIds', 'selected', 'maxTopic', 'zadaniya', 'taskCount'
        ));
    }

    public function test(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->firstOrFail();

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect()->route('pwa.student.ege.results', $attempt->id);
        }

        $variant = $attempt->variant;
        $tasks   = $this->normalizeTaskImageViewerMeta($variant, $variant->config_json['tasks'] ?? []);
        $answers = $attempt->answers()->pluck('current_answer', 'task_number');
        $mode    = $variant->mode ?? 'full';
        $title   = $variant->title ?? 'Вариант ЕГЭ';

        return view('pwa.student.ege-test', compact(
            'attempt', 'tasks', 'answers', 'mode', 'title'
        ));
    }

    public function results(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->where('status', 'submitted')
            ->with(['variant', 'scorings'])
            ->firstOrFail();

        $scoring = OgeAttemptScoring::where('attempt_id', $attempt->id)->first();

        return view('pwa.student.ege-results', compact('user', 'attempt', 'scoring'));
    }
}
