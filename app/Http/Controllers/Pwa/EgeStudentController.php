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

    private function makePool(string $level = EgeTaskDataService::LEVEL_PROF): EgeVariantPoolService
    {
        $taskData = $level === EgeTaskDataService::LEVEL_BASE
            ? new EgeTaskDataService($level)
            : app(EgeTaskDataService::class);
        $builder  = new EgeVariantBuilderService($taskData);
        return new EgeVariantPoolService($taskData, $builder);
    }

    /**
     * Единый источник уровня для всего раздела ЕГЭ.
     *
     * Явный query/body-параметр запоминается только у настоящего ученика.
     * Учитель и админ на student-домене могут переключать превью, не меняя
     * собственный профиль.
     */
    private function resolveLevel(Request $request): string
    {
        $user = $request->user();
        $requested = $request->input('level');
        $valid = [EgeTaskDataService::LEVEL_PROF, EgeTaskDataService::LEVEL_BASE];

        if (in_array($requested, $valid, true)) {
            if (!$this->supportsStudentViewContext($request, $user)
                && $user?->role === 'student'
                && $user->ege_level !== $requested) {
                $user->update(['ege_level' => $requested]);
            }

            return $requested;
        }

        if (!$this->supportsStudentViewContext($request, $user)
            && in_array($user?->ege_level, $valid, true)) {
            return $user->ege_level;
        }

        return EgeTaskDataService::LEVEL_PROF;
    }

    public function home(Request $request)
    {
        $user  = Auth::user();
        $level = $this->resolveLevel($request);
        $levelMark = $level === EgeTaskDataService::LEVEL_BASE ? 'Б' : 'П';
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
        $taskData = new EgeTaskDataService($level);
        $topics = array_keys($taskData->getAllTopicsMeta());
        $taskCount = max(array_map('intval', $topics));

        // Слабые темы по ЕГЭ пока не считаются: экран показывает блок только
        // при непустом списке, как и ВПР до появления там статистики.
        $weakTopics = [];

        return view('pwa.student.ege-home', compact(
            'user', 'grade', 'gradeLabel', 'level', 'levelMark', 'taskCount', 'activeList',
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

        $variant = $this->makePool($this->resolveLevel($request))->getOrCreateVariant($user);
        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        // Плитка «Полный вариант» дёргает этот адрес через fetch и ждёт JSON
        // с адресом перехода — так же, как ВПР. Один редирект в ответе fetch
        // проглатывал молча: браузер шёл по нему сам, получал HTML страницы
        // теста, и `res.json()` падал на «Unexpected token '<'». Ученику это
        // показывалось как «Ошибка соединения», и вариант не запускался.
        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('pwa.student.ege.test', $attempt->id)]);
        }

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

        // Уровень: профиль (1–19, две части) или база (1–21, все с кратким
        // ответом). У базы делить нечего — развёрнутых заданий в ней нет.
        $level = $this->resolveLevel($request);
        $isBase = $level === EgeTaskDataService::LEVEL_BASE;
        $taskData = new EgeTaskDataService($level);

        // Часть экзамена: 1–12 дают краткий ответ, 13–19 — развёрнутый.
        // Деление приходит от самого ФИПИ (qkind) и совпадает с номерами.
        $part = $request->query('part') === '2' ? 2 : 1;
        // Ключи карты тем — строки «01»…«21», но PHP превращает «10» и
        // дальше в целые числа, а сравнение ниже строгое. Приводим обратно.
        $partTopics = $isBase
            ? array_map(
                static fn ($topic) => str_pad((string) $topic, 2, '0', STR_PAD_LEFT),
                array_keys($taskData->getAllTopicsMeta())
            )
            : ($part === 2
                ? ['13', '14', '15', '16', '17', '18', '19']
                : ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']);

        $topicIds = collect(array_keys($taskData->getAllTopicsMeta()))
            ->map(fn ($topicId) => str_pad((string) $topicId, 2, '0', STR_PAD_LEFT))
            ->filter(fn (string $topicId) => in_array($topicId, $partTopics, true))
            ->filter(fn (string $topicId) => $taskData->topicDataExists($topicId))
            ->values()
            ->all();

        if ($topicIds === []) {
            $topicIds = [$partTopics[0]];
        }

        $maxTopic = max(array_map('intval', $topicIds));

        $selected = str_pad((string) $request->query('topic', ltrim($topicIds[0], '0')), 2, '0', STR_PAD_LEFT);
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

        if ($isBase) {
            $partLabel = 'базовый уровень';
            $partHint = 'Задания 1–21 · краткий ответ';
        } else {
            $partLabel = $part === 2 ? '2я часть' : '1я часть';
            $partHint = $part === 2 ? 'Задания 13–19 · развёрнутый ответ' : 'Задания 1–12 · краткий ответ';
        }
        $levelMark = $isBase ? 'Б' : 'П';

        return view('pwa.student.ege-tasks', compact(
            'user', 'topicIds', 'selected', 'maxTopic', 'zadaniya', 'taskCount',
            'part', 'partLabel', 'partHint', 'level', 'isBase', 'levelMark'
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
