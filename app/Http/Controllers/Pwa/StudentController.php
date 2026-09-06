<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pwa\Concerns\NormalizesTaskImageViewer;
use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\StarTransaction;
use App\Models\TeacherStudent;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkSolutionPhoto;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\User;
use App\Services\HomeworkPhotoStore;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\MiniAppTaskSanitizer;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\StudentExamAccessService;
use App\Support\TaskConditionFormatter;
use App\Services\OgeVariantBuilderService;
use App\Services\OgeVariantPoolService;
use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use App\Services\VariantTaskNumberResolver;
use App\Services\VprTaskDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    use MiniAppHelpers;
    use NormalizesTaskImageViewer;

    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly StudentExamAccessService $examAccess,
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly OgeVariantPoolService $poolService,
        private readonly TaskDataService $taskData,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
        private readonly MiniAppTaskSanitizer $taskSanitizer,
        private readonly TaskAnswerResolver $answerResolver,
        private readonly HomeworkPhotoStore $photoStore,
    ) {}

    private function base(): string
    {
        return 'https://student.' . config('app.base_domain');
    }

    // Onboarding (GET)
    public function onboarding(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->onboarding_completed_at && !empty($user->first_name) && !empty($user->last_name)) {
            return redirect($this->base() . '/');
        }
        return view('pwa.student.onboarding');
    }

    // Onboarding (POST)
    public function saveOnboarding(Request $request)
    {
        $data = $request->validate([
            'first_name'        => 'required|string|min:2|max:100|regex:/^[А-Яа-яЁёA-Za-z\-\']{2,100}$/u',
            'last_name'         => 'required|string|min:2|max:100|regex:/^[А-Яа-яЁёA-Za-z\-\']{2,100}$/u',
            'name_unverified'   => 'nullable|boolean',
            'grade_num'         => 'required|integer|in:5,6,7,8,9,10,11',
            'grade_letter'      => 'required|string|in:А,Б,В,Г,Д,Е,К,М',
            'school_number'     => 'required|integer|min:1|max:9999',
            'city'              => 'nullable|string|max:80',
        ], [
            'first_name.regex' => 'Имя может содержать только буквы, дефис и апостроф.',
            'last_name.regex'  => 'Фамилия может содержать только буквы, дефис и апостроф.',
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $names = app(\App\Services\NameDictionaryService::class);
        $first = $names->capitalize($data['first_name']);
        $last  = $names->capitalize($data['last_name']);
        $unverifiedFlag = (bool) ($data['name_unverified'] ?? false);

        if (!$unverifiedFlag && !$names->isKnownName($first)) {
            return back()->withInput()->withErrors([
                'first_name' => 'Имя «' . $first . '» не найдено в списке. Если вы ввели имя правильно, отметьте «моё имя отсутствует в списке».',
            ]);
        }

        $user->update([
            'first_name'              => $first,
            'last_name'               => $last,
            'name'                    => trim("{$first} {$last}"),
            'name_unverified'         => $unverifiedFlag,
            'grade_num'               => $data['grade_num'],
            'grade_letter'            => $data['grade_letter'],
            'school_number'           => $data['school_number'],
            'city'                    => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return redirect($this->base() . '/');
    }

    /**
     * Dashboard — main hub after login.
     */
    public function dashboard(Request $request)
    {
        $user  = Auth::user();
        $previewExam = $this->resolveStudentViewExam($request, $user);
        $grade = (int) ($user->grade_num ?? 9);

        if ($previewExam === 'vpr') {
            return redirect()->route('pwa.student.vpr.home', [
                'grade' => $this->resolveStudentViewVprGrade($request, $user),
            ]);
        }

        if ($previewExam === 'ege') {
            return redirect()->route('pwa.student.ege.home');
        }

        if ($previewExam === 'oge') {
            $grade = 9;
        }

        // Перенаправить на нужный дашборд по классу
        if ($grade >= 5 && $grade <= 8) {
            return redirect()->route('pwa.student.vpr.home');
        }
        if ($grade >= 10 && $grade <= 11) {
            return redirect()->route('pwa.student.ege.home');
        }
        if ($grade === 12) {
            return redirect()->route('pwa.student.history');
        }

        // grade === 9 → существующий ОГЭ dashboard (продолжаем)

        // Weak topics (from all submitted/scored attempts)
        $weakTopics = $this->computeWeakTopics($user->id);

        $newFipiCount = $this->countNewFipiTasks();

        // Find all unfinished attempts for resume banner
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->with('variant')
            ->orderByDesc('last_seen_at');

        $this->examAccess->applyAttemptAccessScope($activeAttempts, $user);

        $activeAttempts = $activeAttempts->get();

        $activeAttemptsList = [];
        foreach ($activeAttempts as $att) {
            $variant = $att->variant;
            if (!$variant) continue;
            $answeredCount = $att->answers()->count();
            $totalCount = count($variant->config_json['tasks'] ?? []);
            $mode = $variant->mode ?? 'full';
            $isMini = str_starts_with($mode, 'mini_');
            $activeAttemptsList[] = [
                'id' => $att->id,
                'title' => $variant->title ?? 'Вариант ОГЭ',
                'answeredCount' => $answeredCount,
                'totalCount' => $totalCount,
                'startedAt' => $att->started_at,
                'type' => match ($mode) {
                    'mini_algebra' => 'Мини-ОГЭ · Алгебра',
                    'mini_geometry' => 'Мини-ОГЭ · Геометрия',
                    'mini_mixed' => 'Мини-ОГЭ · Смешанное',
                    'mini_part2' => 'Мини-ОГЭ · 2-я часть',
                    'full' => 'Полный вариант',
                    'part2' => '2-я часть',
                    'tasks_part1' => '1-я часть',
                    default => $isMini ? 'Мини-ОГЭ' : 'Вариант ОГЭ',
                },
            ];
        }

        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();
        // Плитка «УРОК»: прикреплённым ученикам и админу (превью интерфейса ученика).
        $showLessonTile = $hasTeacher || $user->isAdmin();

        return view('pwa.student.dashboard', compact(
            'user', 'weakTopics', 'newFipiCount', 'activeAttemptsList', 'hasTeacher', 'showLessonTile'
        ));
    }

    /**
     * OGE dashboard для 8 класса (тумблер ВПР) и 10–11 (тумблер ЕГЭ) — повторение ОГЭ.
     */
    public function ogeDashboard(Request $request)
    {
        $user  = Auth::user();
        $grade = (int) ($user->grade_num ?? 9);

        // Доступен 8, 10, 11 классам (у 9 ОГЭ — дефолт на «/»); остальные — на свой дашборд.
        if (!in_array($grade, [8, 10, 11], true)) {
            return redirect()->route('pwa.student.dashboard');
        }

        $weakTopics = $this->computeWeakTopics($user->id);
        $newFipiCount = $this->countNewFipiTasks();

        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->with('variant')
            ->orderByDesc('last_seen_at');

        $this->examAccess->applyAttemptAccessScope($activeAttempts, $user);

        $activeAttempts = $activeAttempts->get();

        $activeAttemptsList = [];
        foreach ($activeAttempts as $att) {
            $variant = $att->variant;
            if (!$variant) continue;
            $answeredCount = $att->answers()->count();
            $totalCount = count($variant->config_json['tasks'] ?? []);
            $mode = $variant->mode ?? 'full';
            $isMini = str_starts_with($mode, 'mini_');
            $activeAttemptsList[] = [
                'id' => $att->id,
                'title' => $variant->title ?? 'Вариант ОГЭ',
                'answeredCount' => $answeredCount,
                'totalCount' => $totalCount,
                'startedAt' => $att->started_at,
                'type' => match ($mode) {
                    'mini_algebra' => 'Мини-ОГЭ · Алгебра',
                    'mini_geometry' => 'Мини-ОГЭ · Геометрия',
                    'mini_mixed' => 'Мини-ОГЭ · Смешанное',
                    'mini_part2' => 'Мини-ОГЭ · 2-я часть',
                    'full' => 'Полный вариант',
                    'part2' => '2-я часть',
                    'tasks_part1' => '1-я часть',
                    default => $isMini ? 'Мини-ОГЭ' : 'Вариант ОГЭ',
                },
            ];
        }

        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();
        // Плитка «УРОК»: прикреплённым ученикам и админу (превью интерфейса ученика).
        $showLessonTile = $hasTeacher || $user->isAdmin();

        return view('pwa.student.dashboard', compact(
            'user', 'weakTopics', 'newFipiCount', 'activeAttemptsList', 'hasTeacher', 'showLessonTile'
        ));
    }

    /**
     * New FIPI tasks showcase by topic.
     */
    public function newTasks(Request $request)
    {
        $topics = ['09', '10', '15', '16', '17'];
        $selected = str_pad((string) $request->query('topic', '09'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topics, true)) {
            $selected = '09';
        }

        $newByTopic = [];
        $groupedByTopic = [];

        foreach ($topics as $topicId) {
            $data = $this->taskData->getTopicData($topicId);
            $tasks = [];
            $allTasksIndexed = [];

            foreach (($data['blocks'] ?? []) as $block) {
                foreach (($block['zadaniya'] ?? []) as $zadanie) {
                    foreach (($zadanie['tasks'] ?? []) as $t) {
                        $tid = $t['id'] ?? null;
                        if (is_int($tid)) {
                            $allTasksIndexed[$tid] = $t;
                        }
                    }

                    if (($zadanie['label'] ?? null) !== 'Новые задания') {
                        continue;
                    }
                    $tasks = $zadanie['tasks'] ?? [];
                }
            }

            $all = [];
            foreach ($tasks as $task) {
                $text = trim((string) ($task['text'] ?? ''));
                if ($text === '') continue;

                $all[] = [
                    'id' => $task['id'] ?? null,
                    'src_key' => $task['src_key'] ?? null,
                    'text' => $text,
                    'svg' => $task['svg'] ?? null,
                    'image' => $task['image'] ?? null,
                    'answer' => $task['answer'] ?? null,
                ];
            }
            $newByTopic[$topicId] = $all;

            if ($topicId === '10') {
                $spec = [
                    ['title' => 'Орёл или решка', 'ids' => array_merge(range(85, 94), range(77, 80))],
                    ['title' => 'Ящик с карандашами', 'ids' => array_merge(range(95, 104), range(65, 68))],
                    ['title' => 'Маркеры для доски', 'ids' => array_merge(range(105, 114), range(61, 64))],
                    ['title' => 'Вероятность события в опыте', 'ids' => array_merge(range(115, 124), range(73, 76))],
                    ['title' => 'Диаграмма Эйлера', 'ids' => range(125, 164)],
                    ['title' => 'Дерево случайного опыта', 'ids' => range(165, 174)],
                ];

                $groups = [];
                foreach ($spec as $groupSpec) {
                    $groupTasks = [];
                    foreach ($groupSpec['ids'] as $id) {
                        $task = $allTasksIndexed[$id] ?? null;
                        if (!$task) continue;
                        $text = trim((string) ($task['text'] ?? ''));
                        if ($text === '') continue;

                        $groupTasks[] = [
                            'id' => $task['id'] ?? null,
                            'src_key' => $task['src_key'] ?? null,
                            'text' => $text,
                            'svg' => $task['svg'] ?? null,
                            'image' => $task['image'] ?? null,
                            'answer' => $task['answer'] ?? null,
                        ];
                    }
                    if (!empty($groupTasks)) {
                        $groups[] = ['title' => $groupSpec['title'], 'tasks' => $groupTasks];
                    }
                }
                $groupedByTopic[$topicId] = $groups;
            } else {
                $groupedByTopic[$topicId] = [];
            }
        }

        return view('pwa.student.new-tasks', [
            'topics' => $topics,
            'selectedTopic' => $selected,
            'newByTopic' => $newByTopic,
            'groupedByTopic' => $groupedByTopic,
            'isPremium' => true, // No billing gate in PWA
            'trialUsed' => true,
        ]);
    }

    /**
     * OGE Part 2 tasks (topics 20-25).
     */
    public function part2(Request $request)
    {
        $topicsMeta = [
            '20' => ['title' => 'Уравнения', 'icon' => '🔢'],
            '21' => ['title' => 'Текстовые задачи', 'icon' => '🚗'],
            '22' => ['title' => 'Графики функций', 'icon' => '📈'],
            '23' => ['title' => 'Геометрия (вычисление)', 'icon' => '📐'],
            '24' => ['title' => 'Геометрия (доказательство)', 'icon' => '✏️'],
            '25' => ['title' => 'Геометрия (повышенной сложности)', 'icon' => '🧭'],
        ];

        $topics = array_keys($topicsMeta);
        $selected = (string) $request->query('topic', '20');
        if (!in_array($selected, $topics)) {
            $selected = '20';
        }

        $user = $request->user();
        $isTeacher = $user && ($user->isTeacher() || $user->isAdmin());

        $data = $this->taskData->getTopicData($selected);
        $zadaniya = [];
        $shortLabels = $this->part2ShortLabels()[$selected] ?? [];

        foreach (($data['blocks'] ?? []) as $block) {
            $blockSection = trim((string) ($block['title'] ?? ''));
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text = trim((string) ($t['text'] ?? ''));
                    $html = trim((string) ($t['html'] ?? ''));   // условие из банка ФИПИ
                    if ($text === '' && $html === '') continue;
                    [$drawing, $htmlRest] = $this->extractSingleDrawing($html);
                    $tasks[] = [
                        'id'      => $t['id'] ?? null,
                        'drawing' => $drawing,
                        'html'    => $htmlRest !== '' ? $htmlRest : null,
                        'text'   => $text,
                        'image'  => $t['image'] ?? null,
                        'answer' => $t['answer'] ?? null,
                        'subtype' => isset($t['subtype']) ? (int) $t['subtype'] : null,
                    ];
                }
                if (!empty($tasks)) {
                    $section = trim((string) ($zadanie['section'] ?? ''));
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $num = (int) ($zadanie['number'] ?? 0);
                    $title = $section !== '' ? $section : ($instruction !== '' ? "Задание {$num}. {$instruction}" : "Задание {$num}");
                    $short = $shortLabels[$num] ?? null;
                    $isCurated = trim((string) ($zadanie['taxonomy_key'] ?? '')) !== '';
                    // Решение видно только учителям/админам; его текст НЕ отдаётся ученику.
                    $hasSolution = $isTeacher && trim((string) ($zadanie['solution'] ?? '')) !== '';
                    $zadaniya[] = [
                        'number'       => $num,
                        'section'      => $isCurated ? $blockSection : '',
                        'title'        => $isCurated
                            ? ($instruction !== '' ? $instruction : "Группа {$num}")
                            : ($short['title'] ?? $title),
                        'subtitle'     => $isCurated
                            ? count($tasks) . ' заданий'
                            : ($short['subtitle'] ?? null),
                        'hint'         => $zadanie['answer_hint'] ?? null,
                        'tasks'        => $tasks,
                        'subtypes'     => $this->groupSubtypes($zadanie['subtypes'] ?? null, $tasks),
                        'has_solution' => $hasSolution,
                    ];
                }
            }
        }

        return view('pwa.student.part2', [
            'topicsMeta'    => $topicsMeta,
            'topics'        => $topics,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'isTeacher'     => $isTeacher,
            'isPremium'     => true, // No billing gate in PWA
            'trialUsed'     => true,  // No trial UI in PWA
        ]);
    }

    /**
     * Задачи группы, разложенные по подтипам, — второй уровень раскрытия.
     * Одинаково нужен обеим витринам банка: и 1-й части, и 2-й.
     * Разметку кладёт `tasks:seed-subtypes`; если она не сошлась с задачами,
     * возвращаем пустой список и группа показывается плоско, как раньше.
     *
     * @param  array<int, string>|null  $titles
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, array{title: string, tasks: array<int, array<string, mixed>>}>
     */
    private function groupSubtypes(?array $titles, array $tasks): array
    {
        if (empty($titles)) {
            return [];
        }

        $subtypes = [];
        foreach (array_values($titles) as $i => $title) {
            $subtypes[$i] = ['title' => (string) $title, 'tasks' => []];
        }

        foreach ($tasks as $task) {
            $i = $task['subtype'];
            if ($i === null || !isset($subtypes[$i])) {
                return [];
            }
            $subtypes[$i]['tasks'][] = $task;
        }

        foreach ($subtypes as $subtype) {
            if ($subtype['tasks'] === []) {
                return [];
            }
        }

        return array_values($subtypes);
    }

    /**
     * Проверка ответа к заданию второй части в разделе «2я часть ОГЭ».
     * Ответ ученика сверяется на сервере, чтобы работал общий разбор
     * выражений с корнями (TaskAnswerResolver → MathAnswerParser).
     */
    public function part2Check(Request $request)
    {
        $data = $request->validate([
            'topic' => 'required|string|max:2',
            'zadanie' => 'required|integer|min:1',
            'task_id' => 'required|integer|min:1',
            'answer' => 'nullable|string|max:255',
            'reveal' => 'nullable|boolean',
        ]);

        $correct = $this->part2TaskAnswer($data['topic'], (int) $data['zadanie'], (int) $data['task_id']);
        if ($correct === null) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (!empty($data['reveal'])) {
            return response()->json(['status' => 'revealed', 'answer' => $correct]);
        }

        $answer = trim((string) ($data['answer'] ?? ''));
        if ($answer === '') {
            return response()->json(['error' => 'empty_answer'], 422);
        }

        return response()->json([
            'status' => 'checked',
            'correct' => (bool) $this->answerResolver->isCorrect($answer, $correct),
        ]);
    }

    /** Эталонный ответ конкретной задачи второй части или null, если её нет. */
    private function part2TaskAnswer(string $topic, int $zadanieNumber, int $taskId): ?string
    {
        if (!in_array($topic, ['20', '21', '23', '24', '25'], true)) {
            return null;
        }

        $data = $this->taskData->getTopicData($topic);
        foreach (($data['blocks'] ?? []) as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) !== $zadanieNumber) {
                    continue;
                }
                foreach (($zadanie['tasks'] ?? []) as $task) {
                    if ((int) ($task['id'] ?? 0) !== $taskId) {
                        continue;
                    }
                    $answer = trim((string) ($task['answer'] ?? ''));

                    return $answer === '' ? null : $answer;
                }
            }
        }

        return null;
    }

    /**
     * Подробное решение задания части 2 — ТОЛЬКО для учителей/админов
     * (роут защищён middleware role:teacher,admin). Текст решения и SVG живут
     * в поле `solution` соответствующего zadanie в topic_{N}.json.
     */
    public function part2Solution(Request $request, string $topic, int $number)
    {
        if (!in_array($topic, ['20', '21', '22', '23', '24', '25'], true)) {
            abort(404);
        }

        $data = $this->taskData->getTopicData($topic);
        $found = null;
        $blockTitle = '';
        foreach (($data['blocks'] ?? []) as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                if ((int) ($zadanie['number'] ?? 0) === $number) {
                    $found = $zadanie;
                    $blockTitle = trim((string) ($block['title'] ?? ''));
                    break 2;
                }
            }
        }

        $solution = trim((string) ($found['solution'] ?? ''));
        if ($found === null || $solution === '') {
            abort(404);
        }

        $instruction = trim((string) ($found['instruction'] ?? ''));

        // Заголовки part2ShortLabels() описывают серии прежнего банка и после
        // педагогической перегруппировки ФИПИ к её номерам уже не относятся:
        // у кураторской группы заголовок берём из неё самой, как в списке.
        if (trim((string) ($found['taxonomy_key'] ?? '')) !== '') {
            $title = $instruction !== '' ? $instruction : "Группа {$number}";
            $subtitle = $blockTitle !== '' ? $blockTitle : null;
            $instruction = '';   // уже вынесена в заголовок, в рамке не дублируем
        } else {
            $short = $this->part2ShortLabels()[$topic][$number] ?? null;
            $title = $short['title'] ?? "Задание {$number}";
            $subtitle = $short['subtitle'] ?? null;
        }

        // Раздел «2я часть» открывает разбор всплывающим окном и просит только
        // содержимое; отдельная страница по тому же адресу остаётся рабочей.
        $view = $request->boolean('fragment')
            ? 'pwa.student.part2-solution-fragment'
            : 'pwa.student.part2-solution';

        return view($view, [
            'topic'        => $topic,
            'number'       => $number,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'instruction'  => $instruction,
            'solution'     => $solution,
        ]);
    }

    private function part2ShortLabels(): array
    {
        return [
            '20' => [
                1  => ['title' => 'Значение выражения',     'subtitle' => 'По данному отношению'],
                2  => ['title' => 'Кубическое уравнение',   'subtitle' => 'Группировка'],
                3  => ['title' => 'Уравнение с корнем',     'subtitle' => 'Перенос радикала'],
                4  => ['title' => 'Произведение скобок',    'subtitle' => 'С квадратом суммы'],
                5  => ['title' => 'Дробно-рациональное',    'subtitle' => 'Замена 1/x = t'],
                6  => ['title' => 'Биквадратное',           'subtitle' => 'Замена (x ± a)² = t'],
                7  => ['title' => 'Степенное уравнение',    'subtitle' => 'x⁴ = (·)²'],
                8  => ['title' => 'Система уравнений',      'subtitle' => 'Подстановка'],
                9  => ['title' => 'Однородная система',     'subtitle' => 'Деление уравнений'],
                10 => ['title' => 'Неравенство с корнем',   'subtitle' => 'Замена (x − a)'],
                11 => ['title' => 'Дробное неравенство',    'subtitle' => 'Метод интервалов'],
            ],
            '21' => [
                1  => ['title' => 'Движение по прямой',     'subtitle' => 'Один объект'],
                2  => ['title' => 'Движение навстречу',     'subtitle' => 'По прямой'],
                3  => ['title' => 'Движение вдогонку',      'subtitle' => 'Автомобили'],
                4  => ['title' => 'Движение вдогонку',      'subtitle' => 'Велосипедисты'],
                5  => ['title' => 'Движение вдогонку',      'subtitle' => 'Половина пути'],
                6  => ['title' => 'По окружности',          'subtitle' => 'Замкнутая трасса'],
                7  => ['title' => 'Средняя скорость',       'subtitle' => 'Найти среднюю'],
                8  => ['title' => 'Протяжённые тела',       'subtitle' => 'Найти длину поезда'],
                9  => ['title' => 'Движение по воде',       'subtitle' => 'Баржа'],
                10 => ['title' => 'Движение по воде',       'subtitle' => 'Плот и лодка'],
                11 => ['title' => 'Движение по воде',       'subtitle' => 'Моторная лодка'],
                12 => ['title' => 'Движение по воде',       'subtitle' => 'Теплоход'],
                13 => ['title' => 'Проценты',               'subtitle' => 'Концентрация'],
                14 => ['title' => 'Проценты',               'subtitle' => 'Фрукты'],
                15 => ['title' => 'Совместная работа',      'subtitle' => 'Производительность'],
            ],
            '23' => [
                1  => ['title' => 'Параллелограмм · биссектриса', 'subtitle' => 'Найти периметр'],
                2  => ['title' => 'Ромб · диагонали',             'subtitle' => 'Найти углы ромба'],
                3  => ['title' => 'Ромб · высота',                'subtitle' => 'Найти высоту ромба'],
                4  => ['title' => 'Прямоугольный треугольник',    'subtitle' => 'Найти медиану по катетам'],
                5  => ['title' => 'Высота к гипотенузе',          'subtitle' => 'Прямоугольный треугольник'],
                6  => ['title' => 'Трапеция · биссектрисы',       'subtitle' => 'Найти боковую сторону AB'],
                7  => ['title' => 'Подобие треугольников',        'subtitle' => 'Найти отрезок BN'],
                8  => ['title' => 'Параллельные прямые',          'subtitle' => 'Найти отрезок MC'],
                9  => ['title' => 'Высота из прямого угла',       'subtitle' => 'Найти AB'],
                10 => ['title' => 'Трапеция · углы',              'subtitle' => 'Найти боковую сторону AB'],
                11 => ['title' => 'Трапеция · параллельная прямая','subtitle' => 'Найти отрезок EF'],
                12 => ['title' => 'Хорды окружности',             'subtitle' => 'Найти длину хорды CD'],
                13 => ['title' => 'Высота прямоугольного Δ',      'subtitle' => 'Найти BH или PK'],
                14 => ['title' => 'Окружность · стороны Δ',       'subtitle' => 'Найти отрезок KP'],
                15 => ['title' => 'Окружность на стороне Δ',      'subtitle' => 'Найти AC или диаметр'],
                16 => ['title' => 'Описанная окружность',         'subtitle' => 'Найти BC по углам и радиусу'],
            ],
            '24' => [
                1  => ['title' => 'Прямая через центр',          'subtitle' => 'Равные отрезки на сторонах'],
                2  => ['title' => 'Сторона вдвое больше',         'subtitle' => 'Отрезок к середине — биссектриса'],
                3  => ['title' => 'Биссектрисы параллелограмма',  'subtitle' => 'Точка — середина стороны'],
                4  => ['title' => 'Биссектрисы 4-угольника',      'subtitle' => 'Равноудалённость от прямых'],
                5  => ['title' => 'Точка внутри параллелограмма', 'subtitle' => 'Сумма площадей = ½ площади'],
                6  => ['title' => 'Диагонали трапеции',           'subtitle' => 'Равные площади треугольников'],
                7  => ['title' => 'Середина боковой стороны',     'subtitle' => 'Площадь = ½ площади трапеции'],
                8  => ['title' => 'Точка на средней линии',       'subtitle' => 'Сумма площадей = ½ площади'],
                9  => ['title' => 'Основания и диагональ',        'subtitle' => 'Подобие треугольников'],
                10 => ['title' => 'Вписанный 4-угольник',         'subtitle' => 'Подобие при продолжении сторон'],
                11 => ['title' => 'Тупой угол · высоты',          'subtitle' => 'Подобие ортотреугольнику'],
                12 => ['title' => 'Остроугольный · высоты',       'subtitle' => 'Равные углы'],
                13 => ['title' => 'Выпуклый 4-угольник · углы',   'subtitle' => 'Равные вписанные углы'],
                14 => ['title' => 'Две окружности',               'subtitle' => 'Линия центров ⊥ хорде'],
                15 => ['title' => 'Общая касательная',            'subtitle' => 'Отношение диаметров a:b'],
            ],
            '25' => [
                1  => ['title' => 'Трапеция · биссектриса',        'subtitle' => 'Найти площадь'],
                2  => ['title' => 'Трапеция · углы и отрезки',     'subtitle' => 'Найти основания'],
                3  => ['title' => 'Вписанная окружность',          'subtitle' => 'Расстояние до основания'],
                4  => ['title' => 'Параллелограмм · инцентр',      'subtitle' => 'Найти площадь'],
                5  => ['title' => 'Параллелограмм · биссектрисы',  'subtitle' => 'Найти площадь'],
                6  => ['title' => 'Биссектриса = медиана ⊥',       'subtitle' => 'Найти стороны'],
                7  => ['title' => 'Биссектриса делит высоту',      'subtitle' => 'Радиус описанной'],
                8  => ['title' => 'Трапеция · сумма углов 90°',    'subtitle' => 'Радиус окружности'],
                9  => ['title' => 'Трапеция · касательная',        'subtitle' => 'Расстояние до CD'],
                10 => ['title' => 'Две окружности · касательные',  'subtitle' => 'Расстояние между AB и CD'],
                11 => ['title' => 'Описанная · BD ⊥ AO',           'subtitle' => 'Найти CD'],
                12 => ['title' => 'Полуокружность на BC',          'subtitle' => 'Найти AH'],
                13 => ['title' => 'Середина равноудалена',         'subtitle' => 'Найти AD'],
                14 => ['title' => 'Вписанный 4-угольник · 60°',    'subtitle' => 'Найти радиус'],
                15 => ['title' => 'Касательная к лучу',            'subtitle' => 'Найти радиус'],
            ],
        ];
    }

    /**
     * OGE Part 1 tasks (topics 06-19, production status only).
     */

    /**
     * Вынести единственный чертёж из разметки условия.
     *
     * У банка ФИПИ условие и рисунок лежат в соседних ячейках таблицы, и
     * ячейка зажимает чертёж в узкую полоску. Если рисунок в задании один,
     * показываем его отдельным блоком над текстом — крупно и во всю ширину
     * карточки. Задания с несколькими рисунками (соответствия темы 11, где
     * график привязан к своему номеру) не трогаем: там место рисунка значащее.
     *
     * @return array{0: ?string, 1: string}  [чертёж, оставшаяся разметка]
     */
    private function extractSingleDrawing(string $html): array
    {
        if (substr_count($html, '<svg') !== 1) {
            return [null, $html];
        }
        if (!preg_match('/<svg\b.*?<\/svg>/is', $html, $m)) {
            return [null, $html];
        }

        $rest = str_replace($m[0], '', $html);
        // Пустые обёртки, оставшиеся от вынутого рисунка.
        $rest = preg_replace('/<p>\s*<\/p>|<td>\s*<\/td>/i', '', $rest) ?? $rest;

        return [$m[0], $rest];
    }

    public function tasksPart1(Request $request)
    {
        // Задания 1–5 (практико-ориентированный блок) появились вместе с
        // банком ФИПИ: раньше их в банке просто не было.
        $topicIds = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
                     '11', '12', '13', '14', '15', '16', '17', '18', '19'];
        $selected = str_pad((string) $request->query('topic', '6'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topicIds, true)) {
            $selected = '06';
        }

        $blocks = $this->taskData->getBlocks($selected, 'production');

        $zadaniya = [];
        foreach ($blocks as $block) {
            $blockSection = trim((string) ($block['title'] ?? ''));
            if ($blockSection === 'ФИПИ') {
                $blockSection = '';
            }
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $zadanieType = $zadanie['type'] ?? '';

                if ($zadanieType === 'statements' && !empty($zadanie['statements'])) {
                    $tasks = [];
                    foreach ($zadanie['statements'] as $s) {
                        $text = trim((string) ($s['text'] ?? ''));
                        if ($text === '') continue;
                        $tasks[] = [
                            'id' => $s['id'] ?? null, 'text' => $text, 'expression' => null,
                            'svg' => $s['svg'] ?? null, 'image' => $s['image'] ?? null,
                            'options' => null, 'question' => null, 'answer' => $s['answer'] ?? null,
                        ];
                    }
                    if (!empty($tasks)) {
                        $section = trim((string) ($zadanie['section'] ?? ''));
                        $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                        $label = trim((string) ($zadanie['label'] ?? ''));
                        $num = (int) ($zadanie['number'] ?? count($zadaniya) + 1);
                        $title = $label !== ''
                            ? $label
                            : ($section !== '' ? $section : ($instruction !== '' ? $instruction : "Группа {$num}"));
                        $zadaniya[] = [
                            'number' => $num,
                            'section' => $blockSection,
                            'title' => $title,
                            'tasks' => $tasks,
                        ];
                    }
                    continue;
                }

                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text = trim((string) ($t['text'] ?? ''));
                    $expression = trim((string) ($t['expression'] ?? ''));
                    $question = trim((string) ($t['question'] ?? ''));
                    // Банк ФИПИ несёт условие готовой разметкой: формулы уже
                    // в KaTeX, чертежи инлайновыми SVG. Ни text, ни expression
                    // у таких задач нет, и без этой ветки они отсеивались.
                    $html = trim((string) ($t['html'] ?? ''));

                    if ($html === '' && $text === '' && $expression === '' && empty($t['svg']) && empty($t['image']) && $question === '') {
                        continue;
                    }

                    $rawOptions = $t['options'] ?? null;
                    if (is_array($rawOptions)) {
                        $rawOptions = array_map(function ($o) {
                            // Варианты банка ФИПИ уже свёрстаны. Прогонять их
                            // через latexToUnicode нельзя: он выбрасывает
                            // фигурные скобки, и $\dfrac{45}{19}$ становится
                            // $\dfrac4519$ — на экране «45/19» превращается
                            // в «4/5 19».
                            if (is_array($o) && isset($o['html'])) {
                                return $o;
                            }
                            if (is_array($o)) {
                                foreach (['label', 'text', 'value'] as $k) {
                                    if (isset($o[$k])) {
                                        $o[$k] = MiniAppTaskCanonicalizer::latexToUnicode((string) $o[$k]);
                                    }
                                }
                                return $o;
                            }
                            return MiniAppTaskCanonicalizer::latexToUnicode((string) $o);
                        }, $rawOptions);
                    }

                    [$drawing, $htmlRest] = $this->extractSingleDrawing($html);

                    $tasks[] = [
                        'id'         => $t['id'] ?? null,
                        'subtype'    => isset($t['subtype']) ? (int) $t['subtype'] : null,
                        'drawing'    => $drawing,
                        'html'       => $htmlRest !== '' ? $htmlRest : null,
                        'text'       => $text,
                        'expression' => $expression !== '' ? $expression : null,
                        'svg'        => $t['svg'] ?? null,
                        'image'      => $t['image'] ?? null,
                        'options'    => $rawOptions,
                        'question'   => $question !== '' ? $question : null,
                        'answer'     => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $section = trim((string) ($zadanie['section'] ?? ''));
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $label = trim((string) ($zadanie['label'] ?? ''));
                    $num = (int) ($zadanie['number'] ?? count($zadaniya) + 1);
                    $title = $label !== ''
                        ? $label
                        : ($section !== '' ? $section : ($instruction !== '' ? $instruction : "Группа {$num}"));
                    $zadaniya[] = [
                        'number' => $num,
                        'section' => $blockSection,
                        'title' => $title,
                        'tasks' => $tasks,
                        'subtypes' => $this->groupSubtypes($zadanie['subtypes'] ?? null, $tasks),
                    ];
                }
            }
        }

        $taskCount = array_sum(array_map(fn($z) => count($z['tasks']), $zadaniya));

        return view('pwa.student.tasks-part1', [
            'topicIds'      => $topicIds,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'taskCount'     => $taskCount,
            'isPremium'     => true, // No billing gate in PWA
            'trialUsed'     => true,
        ]);
    }

    /**
     * Mini-OGE mode selection page.
     */
    public function mini()
    {
        return view('pwa.student.mini');
    }

    /**
     * Start a mini-OGE test (POST).
     */
    public function startMini(Request $request)
    {
        $request->validate(['mode' => 'required|string|in:geometry,algebra,mixed,part2']);
        $mode = $request->input('mode');
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Сессия истекла. Перезайдите в приложение.'], 401);
        }

        abort_unless($this->examAccess->canAccessExamType($user, OgeVariant::EXAM_OGE), 403);

        try {
            $variant = $this->poolService->getOrCreateVariant($user, $mode);
        } catch (\Throwable $e) {
            Log::error('PWA Mini start pool error', ['user' => $user->id, 'mode' => $mode, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Нет доступных заданий для этого режима. Попробуйте позже.'], 422);
        }

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);
        } catch (\Throwable $e) {
            Log::error('PWA Mini start attempt error', ['user' => $user->id, 'mode' => $mode, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100)], 500);
        }

        return response()->json(['redirect' => $this->base() . '/test/' . $attempt->id]);
    }

    /**
     * Start a full variant test (POST).
     */
    public function startFull(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Сессия истекла. Перезайдите в приложение.'], 401);
        }

        $variantHash = trim((string) $request->input('variant_hash', ''));

        if ($variantHash === '') {
            abort_unless($this->examAccess->canAccessExamType($user, OgeVariant::EXAM_OGE), 403);

            $type = $request->boolean('part2') ? 'full_with_part2' : 'full';

            try {
                $variant = $this->poolService->getOrCreateVariant($user, $type);
                $variantHash = $variant->hash;
            } catch (\RuntimeException $e) {
                Log::warning('PWA Full start pool error', ['user' => $user->id, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Нет доступных заданий. Попробуйте позже.'], 422);
            }
        }

        $requestedVariant = OgeVariant::where('hash', $variantHash)->firstOrFail();
        abort_unless($this->examAccess->canAccessVariant($user, $requestedVariant), 403);

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variantHash);
        } catch (\Throwable $e) {
            Log::error('PWA Full start attempt error', ['user' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100)], 500);
        }

        return response()->json(['redirect' => $this->attemptRedirectUrl($variant, $attempt->id)]);
    }

    /**
     * Test screen — active attempt.
     */
    public function test(Request $request, int $attemptId)
    {
        $user = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->tap(fn ($query) => $this->examAccess->applyAttemptAccessScope($query, $user))
            ->firstOrFail();

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect($this->base() . '/results/' . $attempt->id);
        }

        $variant = $attempt->variant;
        $config = $variant->config_json ?? [];

        if (!empty($config['tasks'])) {
            $tasks = $config['tasks'];
        } else {
            $builtVariant = $this->variantBuilder->build($variant->hash);
            $tasks = $builtVariant['tasks'] ?? [];
        }

        $tasks = array_map(fn ($task) => is_array($task) ? $this->taskCanonicalizer->normalizeForUi($task) : $task, $tasks);
        $tasks = array_map(fn ($t) => is_array($t) ? $this->taskSanitizer->sanitize($t) : $t, $tasks);
        $tasks = $this->normalizeAttemptTasksForMiniApp($variant, $tasks);

        if (is_array($tasks)) {
            usort($tasks, fn($a, $b) => ((int)($a['display_task_number'] ?? 0)) <=> ((int)($b['display_task_number'] ?? 0)));
        }

        $existingAnswers = $this->resolveExistingAttemptAnswersForMiniApp($attempt, $tasks);

        $mode = $variant->mode ?? 'full';
        $title = $variant->title ?? 'Вариант ОГЭ';

        return view('pwa.student.test', [
            'attempt' => $attempt,
            'tasks' => $tasks,
            'answers' => $existingAnswers,
            'mode' => $mode,
            'title' => $title,
            'battleMode' => false,
        ]);
    }

    private function normalizeAttemptTasksForMiniApp(OgeVariant $variant, array $tasks): array
    {
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) continue;
            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $task['display_task_number'] = $numbers['exam_number'];
            $task['attempt_task_number'] = $numbers['slot'];
            $task['slot'] = $numbers['slot'];
            $task['exam_number'] = $numbers['exam_number'];
            $tasks[$index] = $task;
        }

        return $this->normalizeTaskImageViewerMeta($variant, $tasks);
    }

    private function resolveExistingAttemptAnswersForMiniApp(OgeAttempt $attempt, array $tasks): array
    {
        $existingAnswers = $attempt->answers()->pluck('current_answer', 'task_number')->toArray();
        if (empty($existingAnswers)) return [];

        $variant = $attempt->variant;
        $mapped = [];
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) continue;
            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $slot = $numbers['slot'];
            $examNumber = $numbers['exam_number'];

            if (array_key_exists($slot, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$slot];
                continue;
            }
            if (array_key_exists($examNumber, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$examNumber];
            }
        }
        return $mapped;
    }

    private function attemptRedirectUrl(OgeVariant $variant, int $attemptId): string
    {
        return match ($variant->exam_type) {
            OgeVariant::EXAM_VPR5,
            OgeVariant::EXAM_VPR6,
            OgeVariant::EXAM_VPR7,
            OgeVariant::EXAM_VPR8 => route('pwa.student.vpr.test', $attemptId),
            OgeVariant::EXAM_EGE => route('pwa.student.ege.test', $attemptId),
            default => $this->base() . '/test/' . $attemptId,
        };
    }

    /**
     * Results screen — submitted attempt.
     */
    public function results(int $attemptId)
    {
        $user = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->tap(fn ($query) => $this->examAccess->applyAttemptAccessScope($query, $user))
            ->firstOrFail();

        if (!in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect($this->base() . '/test/' . $attempt->id);
        }

        $scorings = OgeAttemptScoring::where('attempt_id', $attempt->id)->orderBy('task_number')->get();
        $answers = $attempt->answers()->get();

        $attempt->loadMissing('variant');
        $configTaskCount = count($attempt->variant?->config_json['tasks'] ?? []);
        $totalTasks = $configTaskCount > 0 ? $configTaskCount : $scorings->count();
        $correctCount = $scorings->where('is_correct', true)->count();

        $totalTime = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $totalTime = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        return view('pwa.student.results', compact(
            'attempt', 'scorings', 'answers', 'totalTasks', 'correctCount', 'totalTime'
        ));
    }

    /**
     * Student attempt history.
     */
    public function history()
    {
        $user = Auth::user();
        $list = $this->buildHistoryListForStudent($user, $user);

        $backUrl = $this->studentHomeUrl($user);

        return view('pwa.student.history', compact('user', 'list', 'backUrl'));
    }

    /**
     * History route entry point.
     * Students keep opening attempt details by ID.
     * Admins can use `/history/{studentId}` to open a student's history page.
     */
    public function historyEntry(Request $request, int $historyId)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $student = User::where('role', 'student')->find($historyId);

            if ($student) {
                return $this->adminHistory($request, $student->id);
            }
        }

        return $this->historyDetail($request, $historyId);
    }

    /**
     * Admin view of a student's history list.
     */
    public function adminHistory(Request $request, int $studentId)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $student = User::where('role', 'student')->findOrFail($studentId);
        $list = $this->buildHistoryListForStudent($student, $request->user());
        $backUrl = route('pwa.student.dashboard');

        return view('pwa.student.admin-history', compact('student', 'list', 'backUrl'));
    }

    /**
     * Student attempt history — detail view.
     */
    public function historyDetail(Request $request, int $attemptId)
    {
        $user = Auth::user();
        $attempt = $this->loadHistoryAttempt($attemptId, $user->role === 'admin' ? null : $user->id, $user);
        $detail = $this->buildHistoryDetailViewData($attempt);
        $backUrl = $this->studentHomeUrl($user);

        return view('pwa.student.history-detail', array_merge($detail, [
            'user' => $user,
            'backUrl' => $backUrl,
            'student' => $attempt->student,
            'studentContext' => null,
        ]));
    }

    /**
     * Admin view of a specific student's history detail.
     */
    public function adminHistoryDetail(Request $request, int $studentId, int $attemptId)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $student = User::where('role', 'student')->findOrFail($studentId);
        $attempt = $this->loadHistoryAttempt($attemptId, $student->id, $request->user());
        $detail = $this->buildHistoryDetailViewData($attempt);
        $backUrl = $this->base() . '/history/' . $student->id;

        return view('pwa.student.history-detail', array_merge($detail, [
            'user' => $request->user(),
            'backUrl' => $backUrl,
            'student' => $student,
            'studentContext' => $student->name,
        ]));
    }

    private function studentHomeUrl(User $user): string
    {
        $grade = (int) ($user->grade_num ?? 9);

        return match (true) {
            $grade >= 5 && $grade <= 8 => route('pwa.student.vpr.home'),
            $grade >= 10 && $grade <= 11 => route('pwa.student.ege.home'),
            $grade === 12 => route('pwa.student.history'),
            default => route('pwa.student.dashboard'),
        };
    }

    private function buildHistoryListForStudent(User $student, ?User $viewer = null): array
    {
        $attemptsQuery = OgeAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with(['variant:id,hash,title,mode,exam_type,level,config_json', 'scorings:id,attempt_id,is_correct'])
            ->orderByDesc('submitted_at')
            ->limit(50);

        // История = прошлые попытки. Свою историю ученик видит всегда, независимо
        // от текущего класса (иначе после grades:promote 9→10/11→12 вся история
        // прячется через access-scope). Гейтим только просмотр ЧУЖОЙ истории.
        if ($viewer?->role === 'student' && (int) $viewer->id !== (int) $student->id) {
            $this->examAccess->applyAttemptAccessScope($attemptsQuery, $viewer);
        }

        $attempts = $attemptsQuery->get();

        $list = [];
        foreach ($attempts as $att) {
            $correct = $att->scorings->where('is_correct', true)->count();
            $configTaskCount = count($att->variant?->config_json['tasks'] ?? []);
            $total = $configTaskCount > 0 ? $configTaskCount : $att->scorings->count();
            $time = null;
            if ($att->started_at && $att->submitted_at) {
                $time = $att->submitted_at->diffInSeconds($att->started_at);
            }
            $list[] = [
                'id' => $att->id,
                'label' => $this->variantModeLabel($att->variant),
                'hash' => $att->variant->hash ?? null,
                'correct' => $correct,
                'total' => $total,
                'time' => $time,
                'date' => $att->submitted_at,
            ];
        }

        return $list;
    }

    private function loadHistoryAttempt(int $attemptId, ?int $studentId = null, ?User $viewer = null): OgeAttempt
    {
        $query = OgeAttempt::where('id', $attemptId)
            ->whereIn('status', ['submitted', 'scored'])
            ->with([
                'student:id,name,grade_num',
                'variant:id,hash,title,mode,exam_type,level,config_json',
                'answers:id,attempt_id,task_number,current_answer',
                'scorings:id,attempt_id,task_number,is_correct,correct_answer',
            ])
            ->when($studentId !== null, fn ($query) => $query->where('student_id', $studentId));

        // Свою попытку ученик открывает всегда (см. buildHistoryListForStudent):
        // access-scope не должен прятать историю после перевода в следующий класс.
        if ($viewer?->role === 'student' && $studentId !== null && (int) $studentId !== (int) $viewer->id) {
            $this->examAccess->applyAttemptAccessScope($query, $viewer);
        }

        return $query->firstOrFail();
    }

    private function buildHistoryDetailViewData(OgeAttempt $attempt): array
    {
        $correct = $attempt->scorings->where('is_correct', true)->count();
        $variantTasks = $this->canonicalizeHistoryVariantTasks($attempt->variant);
        $configTaskCount = count($variantTasks);
        $total = $configTaskCount > 0 ? $configTaskCount : $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        $taskMap = [];
        $displayNumMap = [];
        if ($attempt->variant && $variantTasks !== []) {
            $resolved = VariantTaskNumberResolver::resolveAll($variantTasks, $attempt->variant);
            foreach ($resolved as $entry) {
                $taskMap[$entry['slot']] = $entry['task'];
                if ($entry['exam_number'] !== $entry['slot']) {
                    $displayNumMap[$entry['slot']] = $entry['exam_number'];
                }
            }
        }

        $wrongTasks = [];
        foreach ($attempt->scorings as $scoring) {
            if ($scoring->is_correct !== false && (int) $scoring->is_correct !== 0) {
                continue;
            }

            $taskNum = (int) $scoring->task_number;
            $studentAnswer = $attempt->answers->firstWhere('task_number', $taskNum);
            $def = $taskMap[$taskNum] ?? [];
            $inner = is_array($def['task'] ?? null) ? $def['task'] : [];

            $condition = TaskConditionFormatter::compose(
                (string) (($def['instruction'] ?? $inner['instruction'] ?? '') ?: ''),
                (string) (($inner['text'] ?? $def['text'] ?? $inner['prompt'] ?? $inner['question'] ?? $inner['condition'] ?? $inner['body'] ?? $inner['content'] ?? '') ?: ''),
                (string) (($def['expression'] ?? $inner['expression'] ?? '') ?: '')
            );

            $instructionText = $condition['instruction'];
            $taskText = $condition['text'];
            $taskExpression = $condition['expression'];

            $rawOptions = $def['options'] ?? $inner['options'] ?? null;
            $taskOptions = is_array($rawOptions) ? array_values($rawOptions) : [];

            $wrongTasks[] = [
                'task_number' => $displayNumMap[$taskNum] ?? $taskNum,
                'task_instruction' => $instructionText,
                'task_text' => $taskText,
                'task_expression' => $taskExpression,
                'task_svg' => (string) (($def['svg'] ?? $inner['svg'] ?? '') ?: ''),
                'task_image' => (string) (($def['image'] ?? $inner['image'] ?? '') ?: ''),
                'task_options' => $taskOptions,
                'student_answer' => (string) (($studentAnswer->current_answer ?? '') ?: '—'),
                'correct_answer' => (string) (($scoring->correct_answer ?? '') ?: '—'),
            ];
        }

        usort($wrongTasks, fn ($a, $b) => $a['task_number'] <=> $b['task_number']);

        return [
            'attempt' => $attempt,
            'label' => $this->variantModeLabel($attempt->variant),
            'correct' => $correct,
            'total' => $total,
            'time' => $time,
            'wrongTasks' => $wrongTasks,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function canonicalizeHistoryVariantTasks(?OgeVariant $variant): array
    {
        $tasks = $variant?->config_json['tasks'] ?? [];
        if (!is_array($tasks) || $tasks === []) {
            return [];
        }

        $examType = (string) ($variant?->exam_type ?? '');
        if (!str_starts_with($examType, 'vpr_')) {
            return $tasks;
        }

        $grade = (int) preg_replace('/\D+/', '', $examType);
        if ($grade < 5 || $grade > 8) {
            return $tasks;
        }

        $taskData = new VprTaskDataService($grade);

        foreach ($tasks as $index => $task) {
            if (!is_array($task) || empty($task['topic_id'])) {
                continue;
            }

            $tasks[$index] = $taskData->canonicalizeLegacyTask((string) $task['topic_id'], $task);
        }

        return $tasks;
    }

    /**
     * Tutor page.
     */
    public function tutor()
    {
        $user = Auth::user();

        $lastAttempt = OgeAttempt::where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->orderByDesc('submitted_at')
            ->first();

        $lastCorrect = 0;
        $lastTotal = 0;
        $weakTopics = $this->computeWeakTopics($user->id);

        if ($lastAttempt) {
            $scorings = OgeAttemptScoring::where('attempt_id', $lastAttempt->id)->get();
            $lastTotal = $scorings->count();
            $lastCorrect = $scorings->where('is_correct', true)->count();
        }

        return view('pwa.student.tutor', compact('lastCorrect', 'lastTotal', 'weakTopics'));
    }

    /**
     * Student homework page.
     */
    public function studentHomework(Request $request)
    {
        $user = $request->user();

        $assignments = \App\Models\HomeworkAssignment::where('student_id', $user->id)
            ->with('homework')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $list = [];
        foreach ($assignments as $a) {
            $hw = $a->homework;
            if (!$hw) continue;
            $list[] = [
                'id' => $a->id,
                'homework_id' => $hw->id,
                'type' => $hw->homework_type,
                'title' => $hw->title,
                'topic_number' => $hw->topic_number,
                'variant_hash' => $hw->variant_hash,
                'status' => $a->status,
                'assigned_at' => $hw->assigned_at,
                'completed_at' => $a->completed_at,
                'is_debt' => $a->isDebt(),
                'reviewed' => $a->reviewed_at !== null,
            ];
        }

        // Долги — наверх: их надо сдать в первую очередь, даже если задали новое.
        $list = collect($list)
            ->sortByDesc(fn (array $item) => $item['is_debt'] ? 1 : 0)
            ->values()
            ->all();

        return view('pwa.student.student-homework', compact('user', 'list'));
    }

    public function showTopicHomework(Request $request, HomeworkAssignment $assignment)
    {
        $user = $request->user();
        abort_unless((int) $assignment->student_id === (int) $user->id, 403);

        $assignment->load(['homework.topicTasks', 'topicTaskSubmissions']);
        abort_unless($assignment->homework?->homework_type === 'topic_photo_practice', 404);

        if ($assignment->status === 'assigned') {
            $assignment->update([
                'status' => 'started',
                'started_at' => now(),
            ]);
        }

        $submissions = $assignment->topicTaskSubmissions->keyBy('homework_topic_task_id');

        return view('pwa.student.homework-topic-practice', [
            'assignment' => $assignment->refresh()->load('homework.topicTasks'),
            'homework' => $assignment->homework,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Тикет на прямую загрузку фото в hw-photos (сервис на VPS).
     *
     * Токен короткоживущий и выдаётся по факту прав на задачу: у ученика в
     * браузере не появляется ничего, чем можно грузить в чужую домашку.
     */
    public function homeworkPhotoTicket(Request $request, HomeworkAssignment $assignment, HomeworkTopicTask $homeworkTask)
    {
        $user = $request->user();
        abort_unless((int) $assignment->student_id === (int) $user->id, 403);

        $assignment->load('homework');
        abort_unless($assignment->homework?->homework_type === 'topic_photo_practice', 404);
        abort_unless((int) $homeworkTask->homework_id === (int) $assignment->homework_id, 404);

        $ticket = $this->photoStore->uploadTicket($assignment, $homeworkTask, (int) $user->id);

        if ($ticket === null) {
            // Хранилище не настроено — страница молча уйдёт на обычную отправку файла.
            return response()->json(['enabled' => false], 200);
        }

        return response()->json($ticket + ['enabled' => true]);
    }

    /**
     * След отправки фото со стороны браузера ученика.
     *
     * Половина сценария (сжатие, загрузка в hw-photos, сам fetch) выполняется
     * на телефоне, и когда там что-то ломается, на сервере не остаётся никаких
     * следов: в БД пусто, в laravel.log пусто. Без этого лога каждый разбор
     * жалобы «не отправляется» превращается в угадывание.
     */
    public function homeworkPhotoLog(Request $request, HomeworkAssignment $assignment, HomeworkTopicTask $homeworkTask)
    {
        $user = $request->user();
        abort_unless((int) $assignment->student_id === (int) $user->id, 403);
        abort_unless((int) $homeworkTask->homework_id === (int) $assignment->homework_id, 404);

        $trail = collect($request->input('trail', []))
            ->take(40)
            ->map(fn ($entry) => [
                'at' => Str::limit((string) data_get($entry, 'at', ''), 20, ''),
                'step' => Str::limit((string) data_get($entry, 'step', ''), 40, ''),
                'detail' => Str::limit((string) data_get($entry, 'detail', ''), 200, ''),
            ])
            ->values()
            ->all();

        Log::channel('hw_photos')->info('homework photo submit trail', [
            'outcome' => Str::limit((string) $request->input('outcome', ''), 40, ''),
            'assignment' => $assignment->id,
            'task' => $homeworkTask->id,
            'task_order' => $homeworkTask->task_order,
            'student' => $user->id,
            'ua' => Str::limit((string) $request->input('ua', ''), 200, ''),
            'trail' => $trail,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Ответ на отправку задачи.
     *
     * Основной клиент отправляет ответ обычным fetch и ждёт JSON: у него нет
     * нативной навигации, поэтому редирект он показать не может, а любой
     * не-JSON ответ (419, 413, страница логина) для него неотличим от тишины.
     * No-JS и фолбэк с файлами по-прежнему получают redirect с флешками.
     *
     * $reload — состояние задачи на сервере изменилось и страница должна
     * перечитать себя; сообщение кладём во флеш, чтобы после перезагрузки его
     * показала та же плашка, что и при обычной отправке.
     */
    private function homeworkTaskJson(Request $request, bool $ok, string $message, bool $reload, string $code = '')
    {
        if ($reload) {
            $request->session()->flash($ok ? 'success' : 'error', $message);
        }

        return response()->json([
            'ok' => $ok,
            'reload' => $reload,
            'message' => $message,
            'code' => $code,
        ]);
    }

    /**
     * Фото собственного решения — нужно ученику на разборе домашки с учителем.
     *
     * Единственная проверка — принадлежность домашки самому ученику:
     * фото → попытка → назначение → student_id. Проверять «то же ДЗ» нельзя:
     * домашка выдаётся на класс (один Homework — много HomeworkAssignment),
     * и такая проверка открыла бы тетради всех одноклассников.
     */
    public function homeworkSolutionPhoto(Request $request, HomeworkSolutionPhoto $photo)
    {
        $user = $request->user();
        $photo->load('submission.assignment');

        $assignment = $photo->submission?->assignment;
        abort_unless($assignment !== null, 404);
        abort_unless((int) $assignment->student_id === (int) $user->id, 403);

        $width = in_array((int) $request->query('w'), [400, 800, 1600], true) ? (int) $request->query('w') : null;

        if ($photo->isRemote()) {
            $url = $this->photoStore->readUrl((string) $photo->remote_id, $width);
            abort_if($url === null, 404);

            return redirect()->away($url);
        }

        $path = (string) $photo->path;
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    public function submitTopicHomeworkTask(Request $request, HomeworkAssignment $assignment, HomeworkTopicTask $homeworkTask)
    {
        $user = $request->user();
        abort_unless((int) $assignment->student_id === (int) $user->id, 403);

        $assignment->load('homework');
        abort_unless($assignment->homework?->homework_type === 'topic_photo_practice', 404);
        abort_unless((int) $homeworkTask->homework_id === (int) $assignment->homework_id, 404);

        // Решение может занимать несколько страниц — принимаем до 10 фото на попытку.
        // Обычный путь: браузер уже загрузил снимки в сервис hw-photos и присылает
        // только их `photo_ids`. Фолбэк (сервис недоступен, старый клиент, no-JS) —
        // файлы приходят сюда: фото тетради с телефона это 3–20 МБ, лимит держим
        // на уровне того, что реально пропускает хостинг.
        // HEIC/HEIF — формат камеры iPhone, правило `image` его не пропускает.
        $max = HomeworkSolutionPhoto::MAX_PER_ATTEMPT;
        $validator = Validator::make($request->all(), [
            'answer' => 'required|string|max:255',
            'photo_ids' => "required_without:solution_photos|nullable|array|max:{$max}",
            'photo_ids.*' => 'string|max:512',
            'solution_photos' => "required_without:photo_ids|nullable|array|max:{$max}",
            'solution_photos.*' => 'file|max:20480|mimes:jpg,jpeg,png,webp,heic,heif,gif,bmp',
        ], [
            'answer.required' => 'Впиши ответ.',
            'answer.max' => 'Ответ слишком длинный.',
            'photo_ids.required_without' => 'Прикрепи фото решения.',
            'solution_photos.required_without' => 'Прикрепи фото решения.',
            'photo_ids.max' => "Больше {$max} страниц решения на одну задачу не принимаем.",
            'solution_photos.max' => "Больше {$max} страниц решения на одну задачу не принимаем.",
            'solution_photos.*.max' => 'Одно из фото слишком тяжёлое (больше 20 МБ). Сфотографируй ещё раз или уменьши качество съёмки.',
            'solution_photos.*.mimes' => 'Нужно фото (jpg, png, heic или webp), а не другой файл.',
            'solution_photos.*.file' => 'Не получилось загрузить фото. Попробуй ещё раз.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                // Перезагружать нечего: ответ и фото у ученика на экране целы,
                // ошибку показываем прямо под кнопкой.
                return $this->homeworkTaskJson($request, false, $validator->errors()->first(), false, 'validation');
            }

            // Ответ и id задачи возвращаем на страницу, чтобы ученик не вбивал его заново,
            // а плашка с ошибкой была видна именно у своей задачи.
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('answer_task_id', $homeworkTask->id)
                ->with('error', "Задача {$homeworkTask->task_order}: " . $validator->errors()->first());
        }

        $validated = $validator->validated();

        $submission = HomeworkTopicTaskSubmission::firstOrNew([
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $homeworkTask->id,
        ]);

        if ($submission->exists && $submission->accepted_at !== null) {
            if ($request->expectsJson()) {
                return $this->homeworkTaskJson($request, true, 'Эта задача уже принята.', true);
            }

            return back()->with('success', 'Эта задача уже принята.');
        }

        $answer = trim((string) $validated['answer']);
        $attemptsCount = min(((int) $submission->attempts_count) + 1, 2);

        $remoteIds = collect($validated['photo_ids'] ?? [])
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->take($max)
            ->values();

        $paths = [];

        if ($remoteIds->isNotEmpty()) {
            // Подпись сервиса подтверждает, что фото загружено именно для этой
            // задачи этим учеником — чужой photo_id не подставить.
            foreach ($remoteIds as $id) {
                if (!$this->photoStore->verifyPhotoId($id, $assignment, $homeworkTask, (int) $user->id)) {
                    Log::warning('Homework photo_id rejected', [
                        'assignment' => $assignment->id,
                        'task' => $homeworkTask->id,
                        'student' => $user->id,
                    ]);

                    $message = "Задача {$homeworkTask->task_order}: фото не подтвердилось хранилищем. Прикрепи его заново — попытка не потрачена.";

                    if ($request->expectsJson()) {
                        // Клиент по этому коду выбрасывает свои photo_id: они уже
                        // ничего не стоят, и повторная отправка тех же даст то же самое.
                        return $this->homeworkTaskJson($request, false, $message, false, 'photo_rejected');
                    }

                    return back()
                        ->withInput()
                        ->with('answer_task_id', $homeworkTask->id)
                        ->with('error', $message);
                }
            }
        } else {
            try {
                foreach (array_slice($request->file('solution_photos', []), 0, $max) as $file) {
                    $paths[] = $file->store("homework_solutions/{$assignment->id}", 'public');
                }
            } catch (\Throwable $e) {
                // Иначе ученик видит белый экран 500 и не понимает, что попытка не сохранилась.
                Log::error('Homework photo store failed', [
                    'assignment' => $assignment->id,
                    'task' => $homeworkTask->id,
                    'error' => $e->getMessage(),
                ]);

                $message = "Задача {$homeworkTask->task_order}: не удалось сохранить фото. Попробуй отправить ещё раз — попытка не потрачена.";

                if ($request->expectsJson()) {
                    return $this->homeworkTaskJson($request, false, $message, false, 'store_failed');
                }

                return back()
                    ->withInput()
                    ->with('answer_task_id', $homeworkTask->id)
                    ->with('error', $message);
            }
        }

        $isCorrect = $this->homeworkAnswerMatches($homeworkTask->correct_answer, $answer);

        $submission->attempts_count = $attemptsCount;
        $submission->is_correct = $isCorrect;

        if ($attemptsCount === 1) {
            $submission->first_answer = $answer;
        } else {
            $submission->second_answer = $answer;
        }

        if ($isCorrect || $attemptsCount >= 2) {
            $submission->accepted_at = now();
        }

        $submission->save();

        // Страницы решения храним отдельно и по попыткам: фото первой попытки
        // остаётся у учителя, даже если ученик переснял решение во второй.
        $position = 0;
        foreach ($remoteIds as $id) {
            $submission->photos()->create([
                'attempt_no' => $attemptsCount,
                'position' => ++$position,
                'remote_id' => $id,
            ]);
        }
        foreach ($paths as $storedPath) {
            $submission->photos()->create([
                'attempt_no' => $attemptsCount,
                'position' => ++$position,
                'path' => $storedPath,
            ]);
        }

        $this->refreshTopicHomeworkProgress($assignment);

        if (!$isCorrect && $attemptsCount === 1) {
            $message = "Задача {$homeworkTask->task_order}: ответ не совпал с правильным. Попробуй ещё раз, фото решения нужно прикрепить снова.";

            // Попытка засчитана — карточка задачи на странице уже другая, её надо перечитать.
            return $request->expectsJson()
                ? $this->homeworkTaskJson($request, false, $message, true)
                : back()->with('error', $message);
        }

        $message = $isCorrect
            ? "Задача {$homeworkTask->task_order} принята: ответ верный."
            : "Задача {$homeworkTask->task_order}: вторая попытка принята. Учитель посмотрит решение по фото.";

        return $request->expectsJson()
            ? $this->homeworkTaskJson($request, true, $message, true)
            : back()->with('success', $message);
    }

    private function homeworkAnswerMatches(?string $correctAnswer, string $answer): bool
    {
        // Единая нормализация ответов (запятая↔точка, дроби, целые с нулями и т.п.),
        // как в основном скоринге попыток — иначе «0,9» не совпадает с «0.9».
        return $this->answerResolver->isCorrect($answer, $correctAnswer) === true;
    }

    private function refreshTopicHomeworkProgress(HomeworkAssignment $assignment): void
    {
        $accepted = HomeworkTopicTaskSubmission::where('homework_assignment_id', $assignment->id)
            ->whereNotNull('accepted_at')
            ->get();
        $total = (int) ($assignment->tasks_total ?: $assignment->homework?->tasks_count ?: $assignment->homework?->topicTasks()->count() ?: 0);

        $isCompleted = $total > 0 && $accepted->count() >= $total;

        $assignment->update([
            'status' => $isCompleted ? 'completed' : 'started',
            'tasks_completed' => $accepted->count(),
            'tasks_correct' => $accepted->where('is_correct', true)->count(),
            'started_at' => $assignment->started_at ?: now(),
            'completed_at' => $isCompleted ? now() : null,
            // Долг закрывается сам, как только работу довели до конца.
            'debt_since' => $isCompleted ? null : $assignment->debt_since,
        ]);
    }

    /**
     * Profile page.
     */
    public function profile()
    {
        $user = Auth::user();

        $transactions = StarTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $referralCount = User::where('referred_by_user_id', $user->id)->count();

        return view('pwa.student.profile', [
            'user'          => $user,
            'transactions'  => $transactions,
            'referralCount' => $referralCount,
            'isPremium'     => true,
            'trialUsed'     => true,
            // Выплаты (teacher_payouts) дропнуты в #44/#49 — pending-заявок не бывает.
            'pendingPayout' => false,
        ]);
    }

    protected function countNewFipiTasks(): int
    {
        $topicIds = ['09', '10', '15', '16', '17'];
        $count = 0;

        foreach ($topicIds as $topicId) {
            $blocks = $this->taskData->getBlocks($topicId);
            foreach ($blocks as $block) {
                foreach (($block['zadaniya'] ?? []) as $zadanie) {
                    $zadanieIsNew = (bool) ($zadanie['is_new'] ?? false);
                    foreach (($zadanie['tasks'] ?? []) as $task) {
                        if ($zadanieIsNew || (bool) ($task['is_new'] ?? false)) {
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
    }

    protected function computeWeakTopics(int $userId): array
    {
        $topicNames = [
            6 => 'Дроби', 7 => 'Числа', 8 => 'Корни', 9 => 'Уравнения',
            10 => 'Вероятность', 11 => 'Графики', 12 => 'Формулы', 13 => 'Неравенства',
            14 => 'Прогрессии', 15 => 'Треугольники', 16 => 'Окружность',
            17 => 'Четырёхугольники', 18 => 'Клетчатая бумага', 19 => 'Высказывания',
        ];

        $stats = DB::table('oge_attempt_scorings as s')
            ->join('oge_attempts as a', 'a.id', '=', 's.attempt_id')
            ->where('a.student_id', $userId)
            ->whereIn('a.status', ['submitted', 'scored'])
            ->select(
                's.task_number',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN s.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('s.task_number')
            ->get();

        if ($stats->isEmpty()) return [];

        return $stats->map(function ($row) use ($topicNames) {
            $pct = $row->total > 0 ? round(($row->correct / $row->total) * 100) : 0;
            return [
                'task_number' => $row->task_number,
                'name' => $topicNames[$row->task_number] ?? "Задание {$row->task_number}",
                'pct' => $pct,
                'total' => $row->total,
                'correct' => $row->correct,
            ];
        })->sortBy('pct')->take(4)->values()->toArray();
    }
}
