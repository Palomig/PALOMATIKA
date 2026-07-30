<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\LessonSession;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\VariantTaskNumberResolver;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    use MiniAppHelpers;

    public function __construct(
        private readonly TaskDataService $taskData,
        private readonly OgeVariantPoolService $poolService,
    ) {}

    private function base(): string
    {
        return 'https://teacher.' . config('app.base_domain');
    }

    public function dashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $studentCount = TeacherStudent::where('teacher_id', $teacherId)->count();
        $aliasedCount = TeacherStudent::where('teacher_id', $teacherId)->whereNotNull('student_alias')->where('student_alias', '!=', '')->count();
        $variantsCount = OgeVariant::where('owner_teacher_id', $teacherId)->count();
        $curatedCount = OgeVariant::where('owner_teacher_id', $teacherId)->where('is_curated', true)->count();

        $myStudentIds = TeacherStudent::where('teacher_id', $teacherId)->pluck('student_id');
        $aliasMap = TeacherStudent::where('teacher_id', $teacherId)->whereNotNull('student_alias')->where('student_alias', '!=', '')->pluck('student_alias', 'student_id');

        $recentAttempts = [];
        if ($myStudentIds->isNotEmpty()) {
            $recentAttempts = OgeAttempt::whereIn('student_id', $myStudentIds)
                ->whereIn('status', ['submitted', 'scored'])
                ->with(['variant:id,hash,title,mode', 'student:id,name', 'scorings:id,attempt_id,is_correct'])
                ->orderByRaw('COALESCE(submitted_at, updated_at) DESC')
                ->limit(15)
                ->get()
                ->map(function (OgeAttempt $att) use ($aliasMap) {
                    $correct = $att->scorings->where('is_correct', true)->count();
                    $configTaskCount = count($att->variant?->config_json['tasks'] ?? []);
                    $total = $configTaskCount > 0 ? $configTaskCount : $att->scorings->count();
                    $time = null;
                    if ($att->started_at && $att->submitted_at) {
                        $time = $att->submitted_at->diffInSeconds($att->started_at);
                    }
                    return [
                        'attempt_id' => $att->id,
                        'student_id' => $att->student_id,
                        'student_name' => $aliasMap[$att->student_id] ?? $att->student?->name ?? '?',
                        'label' => $this->variantModeLabel($att->variant),
                        'correct' => $correct,
                        'total' => $total,
                        'time' => $time,
                        'date' => $att->submitted_at,
                    ];
                })->all();
        }

        return view('pwa.teacher.dashboard', [
            'user' => $user,
            'studentCount' => $studentCount,
            'aliasedCount' => $aliasedCount,
            'variantsCount' => $variantsCount,
            'curatedCount' => $curatedCount,
            'recentAttempts' => $recentAttempts,
        ]);
    }

    public function lessons(Request $request)
    {
        $user = $request->user();

        return view('pwa.teacher.lessons', [
            'days' => $this->buildUpcomingLessonDays($user),
        ]);
    }

    public function students(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;
        $search = trim((string) $request->query('search', ''));
        $filter = trim((string) $request->query('filter', 'mine'));
        $onlineSince = now()->subMinutes(5);
        $recentOnlineSince = now()->subDays(2);
        $onlineScope = trim((string) $request->query('online_scope', 'current'));
        if (!in_array($onlineScope, ['current', 'recent'], true)) {
            $onlineScope = 'current';
        }
        $gradeRaw = trim((string) $request->query('grade', ''));
        $grade = ($gradeRaw !== '' && ctype_digit($gradeRaw) && (int) $gradeRaw >= 5 && (int) $gradeRaw <= 11)
            ? (int) $gradeRaw
            : null;
        $selectedDay = (int) $request->query('day', (int) now()->format('N'));
        if ($selectedDay < 1 || $selectedDay > 7) {
            $selectedDay = (int) now()->format('N');
        }

        $relations = TeacherStudent::where('teacher_id', $teacherId)->with('student:id,name,last_active_at')->get();
        $evriumSlots = $this->fetchEvriumSchedule($user->evrium_teacher_id);
        $daySlots = array_filter($evriumSlots, fn($s) => ($s['day'] ?? 0) === $selectedDay);
        $scheduledStudents = $this->resolveEvriumSlots($daySlots, $relations);
        $scheduledStudentIds = collect($scheduledStudents)
            ->flatMap(fn ($student) => $student['student_ids'] ?? [$student['student_id'] ?? null])
            ->filter()
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values();

        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
        $todayDow = (int) now()->format('N');

        $attemptActivity = DB::table('oge_attempts')
            ->selectRaw('student_id, MAX(COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at)) as attempt_activity_at')
            ->groupBy('student_id');
        $activitySql = 'CASE
            WHEN users.last_active_at IS NULL THEN attempt_activity.attempt_activity_at
            WHEN attempt_activity.attempt_activity_at IS NULL THEN users.last_active_at
            WHEN users.last_active_at >= attempt_activity.attempt_activity_at THEN users.last_active_at
            ELSE attempt_activity.attempt_activity_at
        END';

        $studentsQuery = User::where('users.role', 'student')
            ->leftJoinSub($attemptActivity, 'attempt_activity', function ($join) {
                $join->on('attempt_activity.student_id', '=', 'users.id');
            })
            ->select(['users.id', 'users.name', 'users.email', 'users.avatar', 'users.last_active_at'])
            ->selectRaw($activitySql . ' as activity_at')
            ->selectRaw('(SELECT ts.student_alias FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as student_alias', [$teacherId])
            ->selectRaw('(SELECT ts.created_at FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as linked_at', [$teacherId])
            ->selectRaw('(SELECT ts.evrium_name FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as evrium_name', [$teacherId])
            ->selectRaw('CASE WHEN EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id) THEN 1 ELSE 0 END as is_mine', [$teacherId])
            ->when($search !== '', function ($query) use ($search, $teacherId) {
                $query->where(function ($nested) use ($search, $teacherId) {
                    $nested->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%')
                        ->orWhereRaw('EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id AND ts.student_alias like ?)', [$teacherId, '%' . $search . '%']);
                });
            });

        if ($filter !== 'unlinked') {
            $studentsQuery->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')->from('oge_attempts')->whereColumn('oge_attempts.student_id', 'users.id');
                })->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')->from('teacher_students')->whereColumn('teacher_students.student_id', 'users.id');
                });
            });
        }

        if ($filter === 'mine') {
            $studentsQuery->whereExists(function ($sub) use ($teacherId) {
                $sub->selectRaw('1')->from('teacher_students')->whereColumn('teacher_students.student_id', 'users.id')->where('teacher_students.teacher_id', $teacherId);
            });
        } elseif ($filter === 'online') {
            $studentsQuery->whereExists(function ($sub) use ($teacherId) {
                    $sub->selectRaw('1')->from('teacher_students')->whereColumn('teacher_students.student_id', 'users.id')->where('teacher_students.teacher_id', $teacherId);
                });

            if ($onlineScope === 'recent') {
                $studentsQuery->where(function ($query) use ($recentOnlineSince) {
                    $query->where('users.last_active_at', '>=', $recentOnlineSince)
                        ->orWhere('attempt_activity.attempt_activity_at', '>=', $recentOnlineSince);
                });
            } else {
                $studentsQuery->where(function ($query) use ($onlineSince) {
                    $query->where('users.last_active_at', '>=', $onlineSince)
                        ->orWhere('attempt_activity.attempt_activity_at', '>=', $onlineSince);
                });
            }
        } elseif ($filter === 'unlinked') {
            $studentsQuery->whereNotExists(function ($sub) use ($teacherId) {
                $sub->selectRaw('1')->from('teacher_students')->whereColumn('teacher_students.student_id', 'users.id')->where('teacher_students.teacher_id', $teacherId);
            });
            if ($grade !== null) {
                $studentsQuery->where('users.grade_num', $grade);
            }
        } elseif ($filter === 'scheduled' && $scheduledStudentIds->isNotEmpty()) {
            $studentsQuery->whereIn('users.id', $scheduledStudentIds);
        } elseif ($filter === 'scheduled') {
            $studentsQuery->whereRaw('1 = 0');
        }

        $students = $studentsQuery
            ->addSelect('users.grade_num', 'users.grade_letter')
            ->orderByRaw('COALESCE(' . $activitySql . ', users.created_at) DESC')->orderBy('users.name')->paginate(20)->withQueryString();

        $students->getCollection()->transform(function (User $student) {
            if (!empty($student->activity_at)) {
                $activityAt = Carbon::parse($student->activity_at);
                if ($student->last_active_at === null || $activityAt->gt($student->last_active_at)) {
                    $student->last_active_at = $activityAt;
                }
            }

            return $student;
        });

        $availableGrades = [];
        if ($filter === 'unlinked') {
            $availableGrades = User::where('users.role', 'student')
                ->whereNotNull('users.grade_num')
                ->whereNotExists(function ($sub) use ($teacherId) {
                    $sub->selectRaw('1')->from('teacher_students')->whereColumn('teacher_students.student_id', 'users.id')->where('teacher_students.teacher_id', $teacherId);
                })
                ->distinct()
                ->orderBy('users.grade_num')
                ->pluck('users.grade_num')
                ->map(fn($g) => (int) $g)
                ->unique()
                ->values()
                ->all();
        }

        return view('pwa.teacher.students', [
            'students' => $students,
            'search' => $search,
            'filter' => $filter,
            'onlineSince' => $onlineSince,
            'recentOnlineSince' => $recentOnlineSince,
            'onlineScope' => $onlineScope,
            'grade' => $grade,
            'availableGrades' => $availableGrades,
            'selectedDay' => $selectedDay,
            'todayDow' => $todayDow,
            'dayNames' => $dayNames,
        ]);
    }

    public function variants(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $variants = OgeVariant::where('owner_teacher_id', $teacherId)
            ->withCount('attempts')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pwa.teacher.variants', ['variants' => $variants]);
    }

    public function referrals(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $referrers = User::whereHas('referrals')->withCount('referrals')->orderByDesc('referrals_count')->limit(100)->get(['id', 'name', 'role', 'created_at']);
        $totalUsers = User::count();
        $totalReferred = User::whereNotNull('referred_by_user_id')->count();
        $recentReferrals = User::whereNotNull('referred_by_user_id')->with('referrer:id,name,role')->orderByDesc('created_at')->limit(50)->get(['id', 'name', 'role', 'referred_by_user_id', 'created_at']);

        return view('pwa.teacher.referrals', compact('referrers', 'totalUsers', 'totalReferred', 'recentReferrals'));
    }

    public function studentProfile(Request $request, int $studentId)
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $student = User::where('role', 'student')->findOrFail($studentId);
        $teacherRelation = TeacherStudent::where('teacher_id', $teacher->id)->where('student_id', $student->id)->first();

        $attempts = OgeAttempt::where('student_id', $student->id)
            ->with(['variant:id,hash,title,mode', 'scorings:id,attempt_id,task_number,is_correct'])
            ->orderByRaw('COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $topicStats = [];
        $correctTotal = 0;
        $scoredTotal = 0;

        foreach ($attempts as $attempt) {
            foreach ($attempt->scorings as $scoring) {
                if ($scoring->is_correct === null) continue;
                $taskNum = (int) $scoring->task_number;
                if (!isset($topicStats[$taskNum])) {
                    $topicStats[$taskNum] = ['task_number' => $taskNum, 'correct' => 0, 'total' => 0];
                }
                $topicStats[$taskNum]['total']++;
                $scoredTotal++;
                if ((bool) $scoring->is_correct) { $topicStats[$taskNum]['correct']++; $correctTotal++; }
            }
        }

        usort($topicStats, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        $historyList = [];
        foreach ($attempts as $att) {
            if (!in_array($att->status, ['submitted', 'scored'])) continue;
            $correct = $att->scorings->where('is_correct', true)->count();
            $configTaskCount = count($att->variant?->config_json['tasks'] ?? []);
            $total = $configTaskCount > 0 ? $configTaskCount : $att->scorings->count();
            $time = ($att->started_at && $att->submitted_at) ? $att->submitted_at->diffInSeconds($att->started_at) : null;
            $historyList[] = ['id' => $att->id, 'label' => $this->variantModeLabel($att->variant), 'hash' => $att->variant->hash ?? null, 'correct' => $correct, 'total' => $total, 'time' => $time, 'date' => $att->submitted_at];
        }

        $weakTopics = collect($topicStats)->map(function (array $topic) {
            $accuracy = $topic['total'] > 0 ? (int) round(($topic['correct'] / $topic['total']) * 100) : 0;
            return $topic + ['accuracy' => $accuracy, 'tone' => $accuracy >= 70 ? 'green' : ($accuracy >= 40 ? 'yellow' : 'red')];
        })->sortBy([['accuracy', 'asc'], ['total', 'desc']])->take(5)->values();

        $homeworkHistory = collect();
        if (\Schema::hasTable('homework_assignments') && \Schema::hasTable('homeworks')) {
            $homeworkHistory = HomeworkAssignment::where('student_id', $student->id)
                ->with('homework:id,title,homework_type,topic_number,assigned_at')
                ->orderByDesc('created_at')->limit(8)->get()
                ->map(function (HomeworkAssignment $assignment) {
                    $homework = $assignment->homework;
                    return ['title' => $homework?->title ?: 'Домашнее задание', 'subtitle' => $homework?->assigned_at?->format('d.m.Y H:i') ?: 'Без даты', 'status' => $assignment->status ?: 'assigned'];
                });
        }

        $notes = \App\Models\StudentNote::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('created_at')
            ->get();

        return view('pwa.teacher.student-profile', [
            'student' => $student, 'teacherRelation' => $teacherRelation, 'attempts' => $attempts,
            'topicStats' => $topicStats, 'weakTopics' => $weakTopics, 'correctTotal' => $correctTotal,
            'scoredTotal' => $scoredTotal, 'accuracy' => $scoredTotal > 0 ? (int) round(($correctTotal / $scoredTotal) * 100) : null,
            'historyList' => $historyList, 'homeworkHistory' => $homeworkHistory,
            'notes' => $notes,
        ]);
    }

    public function studentAttemptDetail(Request $request, int $studentId, int $attemptId)
    {
        $student = User::where('role', 'student')->findOrFail($studentId);

        $attempt = OgeAttempt::where('id', $attemptId)->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with(['variant:id,hash,title,mode,config_json', 'answers:id,attempt_id,task_number,current_answer', 'scorings:id,attempt_id,task_number,is_correct,correct_answer'])
            ->firstOrFail();

        $cfg = $attempt->variant?->config_json;
        $configTasks = (is_array($cfg) && isset($cfg['tasks']) && is_array($cfg['tasks'])) ? $cfg['tasks'] : [];

        $correct = $attempt->scorings->where('is_correct', true)->count();
        $total = count($configTasks) ?: $attempt->scorings->count();
        $time = ($attempt->started_at && $attempt->submitted_at) ? $attempt->submitted_at->diffInSeconds($attempt->started_at) : null;

        $taskMap = [];
        if ($attempt->variant && !empty($configTasks)) {
            $resolved = VariantTaskNumberResolver::resolveAll($configTasks, $attempt->variant);
            foreach ($resolved as $entry) { $taskMap[$entry['slot']] = $entry['task']; }
        }

        $wrongTasks = [];
        foreach ($attempt->scorings as $scoring) {
            if ($scoring->is_correct !== false && (int) $scoring->is_correct !== 0) continue;
            $taskNum = (int) $scoring->task_number;
            $studentAnswer = $attempt->answers->firstWhere('task_number', $taskNum);
            $def = $taskMap[$taskNum] ?? [];
            $inner = is_array($def['task'] ?? null) ? $def['task'] : [];

            $instructionText = trim((string) (($def['instruction'] ?? $inner['instruction'] ?? '') ?: ''));
            $conditionText = trim((string) (($inner['text'] ?? $def['text'] ?? $inner['prompt'] ?? $inner['question'] ?? $inner['condition'] ?? $inner['body'] ?? $inner['content'] ?? '') ?: ''));
            if ($instructionText !== '' && $conditionText !== '') {
                if (preg_replace('/\s+/u', ' ', mb_strtolower($instructionText)) === preg_replace('/\s+/u', ' ', mb_strtolower($conditionText))) $instructionText = '';
            }
            $taskText = $conditionText !== '' ? $conditionText : $instructionText;
            $rawOptions = $def['options'] ?? $inner['options'] ?? null;
            $taskOptions = is_array($rawOptions) ? array_values($rawOptions) : [];

            $wrongTasks[] = [
                'task_number' => $taskNum,
                'task_instruction' => $instructionText,
                'task_text' => $taskText,
                'task_expression' => (string) (($def['expression'] ?? $inner['expression'] ?? '') ?: ''),
                'task_svg' => (string) (($def['svg'] ?? $inner['svg'] ?? '') ?: ''),
                'task_image' => (string) (($def['image'] ?? $inner['image'] ?? '') ?: ''),
                'task_options' => $taskOptions,
                'student_answer' => (string) (($studentAnswer->current_answer ?? '') ?: '—'),
                'correct_answer' => (string) (($scoring->correct_answer ?? '') ?: '—'),
            ];
        }

        usort($wrongTasks, fn($a, $b) => $a['task_number'] <=> $b['task_number']);
        $label = $this->variantModeLabel($attempt->variant);
        $backUrl = '/students/' . $studentId;

        return view('pwa.teacher.student-attempt', compact('attempt', 'label', 'correct', 'total', 'time', 'wrongTasks', 'backUrl'));
    }

    public function toggleOwnership(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $student = User::where('id', $studentId)->where('role', 'student')->firstOrFail();
        $relation = TeacherStudent::where('teacher_id', $user->id)->where('student_id', $student->id)->first();

        if ($relation) {
            $relation->delete();
            $isMine = false;
        } else {
            TeacherStudent::create(['teacher_id' => $user->id, 'student_id' => $student->id, 'source' => 'manual']);
            $isMine = true;
        }

        return response()->json(['success' => true, 'is_mine' => $isMine]);
    }

    public function updateAlias(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $payload = $request->validate(['alias' => 'nullable|string|max:80']);
        $alias = trim((string) ($payload['alias'] ?? ''));
        $alias = $alias === '' ? null : $alias;

        $relation = TeacherStudent::where('teacher_id', $user->id)->where('student_id', $studentId)->firstOrFail();
        $relation->student_alias = $alias;
        $relation->save();

        return response()->json(['success' => true, 'alias' => $alias]);
    }

    public function homework(Request $request)
    {
        $user = $request->user();
        $scheduleData = $this->collectTeacherScheduleData($user);
        $relations = $scheduleData['relations'];
        $evriumSlots = $scheduleData['evriumSlots'];
        $currentStudents = $scheduleData['currentStudents'];
        $prevStudents = $scheduleData['prevStudents'];
        $prevDayLabel = $scheduleData['prevDayLabel'];

        $allStudentIds = $relations->pluck('student_id');
        $allStudents = User::whereIn('id', $allStudentIds)->select('id', 'name', 'grade_num')->orderBy('name')->get();

        $relMap = $relations->keyBy('student_id');
        foreach ($allStudents as $s) {
            $s->evrium_name = $relMap[$s->id]->evrium_name ?? null;
            $s->student_alias = $relMap[$s->id]->student_alias ?? null;
        }

        $studentGrades = $allStudents
            ->mapWithKeys(fn (User $s) => [(int) $s->id => $s->grade_num !== null ? (int) $s->grade_num : null])
            ->all();

        $allEvriumNames = collect($evriumSlots)->pluck('students')->flatten()->unique()->sort()->values()->all();
        $profileLinkOptions = $this->collectProfileLinkOptions((int) $user->id);
        $topicOptions = collect($this->taskData->getAllTopicsMeta())
            ->map(fn (array $meta, string $topicId) => [
                'number' => (int) ltrim($topicId, '0'),
                'title' => $meta['title'] ?? "Тема {$topicId}",
            ])
            ->sortBy('number')
            ->values();

        $recentHomework = \App\Models\Homework::where('teacher_id', $user->id)
            ->whereIn('homework_type', ['full_variant', 'topic_practice', 'topic_photo_practice'])
            ->orderByDesc('assigned_at')
            ->limit(30)
            ->get();
        $recentHomework->load('assignments.student:id,name');

        return view('pwa.teacher.homework', compact(
            'user', 'currentStudents', 'prevStudents', 'prevDayLabel', 'allStudents', 'allEvriumNames', 'profileLinkOptions', 'topicOptions', 'recentHomework', 'studentGrades'
        ) + ['todayLabel' => $scheduleData['todayLabel']]);
    }

    public function assignHomework(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'student_id' => 'nullable|exists:users,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'type' => 'required|in:mini_variant,topic_photo_practice',
            'topic_number' => 'nullable|integer',
            'task_indices' => 'nullable|array|max:60',
            'task_indices.*' => 'integer|min:0',
            // Drill-down picker: JSON-массив выбранных задач [{ bank, refs }].
            // Альтернатива task_indices/topic_number для topic_photo_practice.
            'picker_tasks' => 'nullable|string',
            // «Домашка по уроку»: привязка к уроку + своё название (оба опциональны).
            'lesson_session_id' => 'nullable|integer|exists:lesson_sessions,id',
            'title' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
        ]);

        $studentIds = collect($data['student_ids'] ?? [])
            ->push($data['student_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return back()->with('error', 'Выберите ученика.');
        }

        $authorizedIds = TeacherStudent::where('teacher_id', $user->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);

        // «Домашка по уроку»: участники своего урока — тоже законные получатели
        // (вошли по коду и могут ещё не быть в списке учеников).
        $lessonSessionId = (int) $request->input('lesson_session_id', 0);
        if ($lessonSessionId > 0
            && \App\Models\LessonSession::where('id', $lessonSessionId)->where('teacher_id', $user->id)->exists()) {
            $participantIds = \App\Models\LessonSessionParticipant::where('lesson_session_id', $lessonSessionId)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id);
            $authorizedIds = $authorizedIds->merge($participantIds);
        }
        $authorizedIds = $authorizedIds->unique()->values();

        if ($authorizedIds->count() !== $studentIds->count()) {
            return back()->with('error', 'Один из учеников не найден.');
        }

        if ($data['type'] === 'topic_photo_practice') {
            // Новый путь: задачи выбраны через общий drill-down picker (массив { bank, refs }).
            $pickerTasks = $this->decodePickerTasks($data['picker_tasks'] ?? null);
            if (!empty($pickerTasks)) {
                return $this->assignFromPicker($request, $user, $studentIds, $pickerTasks);
            }

            $topicNumber = (int) ($data['topic_number'] ?? 0);
            if ($topicNumber < 1) {
                return back()->with('error', 'Выберите тему.');
            }

            $allTasks = $this->collectAllTopicHomeworkTasks($topicNumber);
            if (count($allTasks) === 0) {
                return back()->with('error', 'В выбранной теме нет задач с ответами.');
            }

            $indices = collect($data['task_indices'] ?? [])
                ->map(fn ($i) => (int) $i)
                ->filter(fn ($i) => $i >= 0 && $i < count($allTasks))
                ->unique()
                ->values();

            if ($indices->isEmpty()) {
                return back()->with('error', 'Выберите хотя бы одну задачу.');
            }

            $tasks = $indices->map(fn (int $i) => $allTasks[$i])->values()->all();
            $tasksCount = count($tasks);

            $homework = new \App\Models\Homework();
            $homework->teacher_id = $user->id;
            $homework->homework_type = 'topic_photo_practice';
            $homework->topic_number = $topicNumber;
            $homework->tasks_count = $tasksCount;
            $homework->title = "Тема {$topicNumber}: {$tasksCount} " . $this->pluralizeTasks($tasksCount) . ' с фото решения';
            $homework->assigned_at = now();
            $homework->save();

            foreach ($tasks as $index => $task) {
                \App\Models\HomeworkTopicTask::create([
                    'homework_id' => $homework->id,
                    'topic_number' => $topicNumber,
                    'task_order' => $index + 1,
                    'task_payload' => $task['payload'],
                    'correct_answer' => $task['answer'],
                ]);
            }

            $assignments = [];
            foreach ($studentIds as $studentId) {
                $assignments[] = \App\Models\HomeworkAssignment::create([
                    'homework_id' => $homework->id,
                    'student_id' => $studentId,
                    'status' => 'assigned',
                    'tasks_total' => $tasksCount,
                ]);
            }

            $this->notifyNewHomework($homework, $assignments);

            return back()->with('success', 'ДЗ выдано!');
        }

        // mini_variant: каждый ученик получает свой вариант — мини-ВПР подбирается
        // по классу ученика, а getOrCreateVariant генерирует вариант per-student,
        // поэтому на каждого выбранного создаём отдельный Homework + assignment.
        $students = User::whereIn('id', $studentIds)->get()->keyBy('id');
        $assignedCount = 0;
        $failed = [];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);
            if (!$student) {
                continue;
            }

            try {
                if ($this->isVprGrade($student)) {
                    $grade = (int) $student->grade_num;
                    $pool = $this->buildVprPool($grade);
                    $variant = $pool->getOrCreateVariant($student, 'mixed');
                    $title = "Мини-ВПР {$grade} класс";
                } else {
                    $variant = $this->poolService->getOrCreateVariant($student, 'mixed');
                    $title = 'Мини-вариант ОГЭ';
                }
            } catch (\RuntimeException $e) {
                $failed[] = $student->name;
                continue;
            }

            $homework = new \App\Models\Homework();
            $homework->teacher_id = $user->id;
            $homework->homework_type = 'full_variant';
            $homework->variant_hash = $variant->hash;
            $homework->title = $title;
            $homework->assigned_at = now();
            $homework->save();

            $assignment = \App\Models\HomeworkAssignment::create([
                'homework_id' => $homework->id,
                'student_id' => $studentId,
                'status' => 'assigned',
            ]);
            // Мини-вариант у каждого свой, поэтому уведомляем сразу по ученику.
            $this->notifyNewHomework($homework, [$assignment]);
            $assignedCount++;
        }

        if ($assignedCount === 0) {
            return back()->with('error', 'Не удалось создать мини-вариант' . ($failed ? ': ' . implode(', ', $failed) : '.'));
        }

        $message = $assignedCount === 1 ? 'Мини-вариант выдан!' : "Мини-вариант выдан ({$assignedCount}).";
        if ($failed) {
            $message .= ' Не удалось для: ' . implode(', ', $failed) . '.';
        }

        return back()->with('success', $message);
    }

    /**
     * Проверка ответов фото-практики: ответы ученика и фото его решения.
     */
    public function homeworkSubmissions(Request $request, HomeworkAssignment $assignment)
    {
        $user = $request->user();
        $assignment->load(['homework.topicTasks', 'topicTaskSubmissions', 'student:id,name']);

        abort_unless($assignment->homework !== null, 404);
        abort_unless($this->canReviewHomework($user, $assignment), 403);
        abort_unless($assignment->homework->homework_type === 'topic_photo_practice', 404);

        return view('pwa.teacher.homework-submissions', [
            'user' => $user,
            'assignment' => $assignment,
            'homework' => $assignment->homework,
            'submissions' => $assignment->topicTaskSubmissions->keyBy('homework_topic_task_id'),
        ]);
    }

    /**
     * Отдаёт фото решения ученика.
     *
     * Через `/storage/...` фото недоступны (на хостинге public/storage — не симлинк),
     * да и тетради учеников не должны лежать по угадываемым публичным ссылкам.
     */
    public function homeworkSolutionPhoto(Request $request, HomeworkTopicTaskSubmission $submission)
    {
        $user = $request->user();
        $submission->load('assignment.homework');

        $assignment = $submission->assignment;
        abort_unless($assignment && $assignment->homework, 404);
        abort_unless($this->canReviewHomework($user, $assignment), 403);

        $path = (string) $submission->solution_photo_path;
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    private function canReviewHomework(User $user, HomeworkAssignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $assignment->homework->teacher_id === (int) $user->id) {
            return true;
        }

        // ДЗ мог выдать другой учитель того же ученика (подмена/замещение).
        return TeacherStudent::where('teacher_id', $user->id)
            ->where('student_id', $assignment->student_id)
            ->exists();
    }

    /**
     * Парсит JSON-массив выбранных picker'ом задач [{ bank, refs }].
     *
     * @return array<int, array{bank:string, refs:array<string,mixed>}>
     */
    private function decodePickerTasks($raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $bank = (string) ($item['bank'] ?? '');
            $refs = $item['refs'] ?? [];
            if ($bank === '' || !is_array($refs)) {
                continue;
            }
            $out[] = ['bank' => $bank, 'refs' => $refs];
        }
        return $out;
    }

    /**
     * Создаёт ДЗ типа topic_photo_practice из задач, выбранных drill-down picker'ом.
     * Каждый { bank, refs } резолвится через TaskBankResolver; недоступные пропускаются.
     */
    private function assignFromPicker(Request $request, User $user, $studentIds, array $pickerTasks)
    {
        $resolver = app(\App\Services\TaskBankResolver::class);

        $resolved = [];
        $skipped = 0;
        foreach ($pickerTasks as $picked) {
            try {
                $r = $resolver->resolve($picked['bank'], $picked['refs']);
            } catch (\DomainException | \InvalidArgumentException $e) {
                $skipped++;
                continue;
            }

            $answer = trim((string) ($r['answer'] ?? ''));
            if ($answer === '') {
                $skipped++;
                continue;
            }

            // topic_number: колонка NOT NULL (unsignedTinyInteger). У alg-skill темы нет —
            // берём topic_id из refs, если он есть и числовой, иначе 0 (нейтрально для рендера,
            // который использует topic_number только для пути к картинкам ОГЭ/ВПР).
            $topicNumber = (int) ($picked['refs']['topic_id'] ?? 0);

            $payload = ($r['raw'] ?? []);
            $payload['expression'] = (string) ($r['expression'] ?? ($payload['expression'] ?? ''));
            $payload['source_label'] = (string) ($r['source_label'] ?? '');
            if (!empty($r['image_svg'])) {
                $payload['svg'] = (string) $r['image_svg'];
            }

            $resolved[] = [
                'topic_number' => $topicNumber,
                'payload' => $payload,
                'answer' => $answer,
            ];
        }

        if (empty($resolved)) {
            return back()->with('error', 'Не удалось добавить ни одной задачи (все недоступны).');
        }

        $tasksCount = count($resolved);

        $homework = new \App\Models\Homework();
        $homework->teacher_id = $user->id;
        $homework->homework_type = 'topic_photo_practice';
        $homework->topic_number = $resolved[0]['topic_number'];
        $homework->tasks_count = $tasksCount;
        $homework->title = "Практика: {$tasksCount} " . $this->pluralizeTasks($tasksCount) . ' с фото решения';

        // «Домашка по уроку»: привязка к своему уроку + переданное название.
        $lessonSessionId = (int) $request->input('lesson_session_id', 0);
        if ($lessonSessionId > 0
            && \App\Models\LessonSession::where('id', $lessonSessionId)->where('teacher_id', $user->id)->exists()) {
            $homework->lesson_session_id = $lessonSessionId;
        }
        if ($customTitle = trim((string) $request->input('title', ''))) {
            $homework->title = mb_substr($customTitle, 0, 160);
        }
        if ($deadline = $request->input('deadline')) {
            $homework->deadline_at = $deadline;
        }

        $homework->assigned_at = now();
        $homework->save();

        foreach ($resolved as $index => $task) {
            \App\Models\HomeworkTopicTask::create([
                'homework_id' => $homework->id,
                'topic_number' => $task['topic_number'],
                'task_order' => $index + 1,
                'task_payload' => $task['payload'],
                'correct_answer' => $task['answer'],
            ]);
        }

        $assignments = [];
        foreach ($studentIds as $studentId) {
            $assignments[] = \App\Models\HomeworkAssignment::create([
                'homework_id' => $homework->id,
                'student_id' => $studentId,
                'status' => 'assigned',
                'tasks_total' => $tasksCount,
            ]);
        }

        $this->notifyNewHomework($homework, $assignments);

        $message = 'ДЗ выдано!';
        if ($skipped > 0) {
            $message .= " Пропущено недоступных задач: {$skipped}.";
        }

        return back()->with('success', $message);
    }

    /**
     * Уведомляет учеников о новом ДЗ (телеграм-канал; in-app покрывает поп-ап).
     *
     * `notified_at` ставим ТОЛЬКО при успешной доставке: иначе недоставленное
     * уведомление выглядит отправленным и никогда не повторяется.
     *
     * @param  array<int, \App\Models\HomeworkAssignment>  $assignments
     */
    private function notifyNewHomework(\App\Models\Homework $homework, array $assignments): void
    {
        $notifier = app(\App\Services\StudentNotifier::class);
        $homeworkUrl = 'https://student.' . config('app.base_domain') . '/homework';
        $tasksCount = (int) $homework->tasks_count;
        $deadline = $homework->deadline_at ? ' Срок: ' . $homework->deadline_at->format('d.m') . '.' : '';
        // У мини-варианта tasks_count не заполняется — тогда без «— N задач».
        $countPart = $tasksCount > 0
            ? ' — ' . $tasksCount . ' ' . $this->pluralizeTasks($tasksCount) . '.'
            : '.';

        foreach ($assignments as $assignment) {
            if ($assignment->notified_at !== null) {
                continue;
            }
            $student = $assignment->student()->first();
            if (!$student) {
                continue;
            }
            $text = '📚 Тебе задали домашку: <b>' . e($homework->title) . '</b>' . $countPart . $deadline;

            if ($notifier->notify($student, $text, $homeworkUrl)) {
                $assignment->update(['notified_at' => now()]);
            }
        }
    }

    private function isVprGrade(User $student): bool
    {
        $grade = (int) ($student->grade_num ?? 0);
        return $grade >= 5 && $grade <= 8;
    }

    private function buildVprPool(int $grade): VprVariantPoolService
    {
        $taskData = new VprTaskDataService($grade);
        $builder = new VprVariantBuilderService($taskData);
        return new VprVariantPoolService($taskData, $builder);
    }

    public function topicTasks(int $topicNumber): \Illuminate\Http\JsonResponse
    {
        $tasks = $this->collectAllTopicHomeworkTasks($topicNumber);
        $list = array_map(function (array $entry, int $index) {
            $payload = $entry['payload'];
            $text = (string) ($payload['text_html'] ?? $payload['text'] ?? $payload['question'] ?? $payload['expression'] ?? $payload['prompt'] ?? '');
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
            if (mb_strlen($text) > 120) {
                $text = mb_substr($text, 0, 117) . '…';
            }

            return [
                'index' => $index,
                'task_order_hint' => $index + 1,
                'instruction' => (string) ($payload['instruction'] ?? ''),
                'text' => $text,
                'answer' => (string) $entry['answer'],
            ];
        }, $tasks, array_keys($tasks));

        return response()->json(['topic_number' => $topicNumber, 'tasks' => $list]);
    }

    private function pluralizeTasks(int $count): string
    {
        $mod100 = $count % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'задач';
        }
        $mod10 = $count % 10;
        if ($mod10 === 1) return 'задача';
        if ($mod10 >= 2 && $mod10 <= 4) return 'задачи';
        return 'задач';
    }

    /**
     * @return array<int, array{payload: array<string, mixed>, answer: string}>
     */
    private function collectAllTopicHomeworkTasks(int $topicNumber): array
    {
        $topicId = str_pad((string) $topicNumber, 2, '0', STR_PAD_LEFT);
        $tasks = $this->collectTopicHomeworkTasks($topicId, $this->taskData->getBlocks($topicId, 'production'));
        if (count($tasks) === 0) {
            $tasks = $this->collectTopicHomeworkTasks($topicId, $this->taskData->getBlocks($topicId));
        }

        return $tasks;
    }

    /**
     * @return array<int, array{payload: array<string, mixed>, answer: string}>
     */
    private function collectTopicHomeworkTasks(string $topicId, array $blocks): array
    {
        $tasks = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $answer = $task['correct_answer'] ?? $task['answer'] ?? null;
                    if (is_array($answer)) {
                        $answer = implode('; ', $answer);
                    }

                    $answer = trim((string) $answer);
                    if ($answer === '') {
                        continue;
                    }

                    $payload = $task;
                    $payload['topic_id'] = $topicId;
                    $payload['instruction'] = $zadanie['instruction'] ?? '';
                    $payload['type'] = $zadanie['type'] ?? 'task';

                    $tasks[] = [
                        'payload' => $payload,
                        'answer' => $answer,
                    ];
                }
            }
        }

        return $tasks;
    }

    public function updateStudentLink(Request $request, int $studentId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['evrium_name' => 'nullable|string|max:100', 'alias' => 'nullable|string|max:80']);
        $student = User::where('role', 'student')->findOrFail($studentId);
        $relation = TeacherStudent::firstOrCreate(
            ['teacher_id' => $user->id, 'student_id' => $student->id],
            ['source' => 'manual']
        );

        if (array_key_exists('evrium_name', $data)) {
            $val = trim((string) ($data['evrium_name'] ?? ''));
            $relation->evrium_name = $val === '' ? null : $val;
        }
        if (array_key_exists('alias', $data)) {
            $val = trim((string) ($data['alias'] ?? ''));
            $relation->student_alias = $val === '' ? null : $val;
        }

        $relation->save();
        return response()->json(['ok' => true]);
    }

    private function collectProfileLinkOptions(int $teacherId): array
    {
        $attemptActivity = DB::table('oge_attempts')
            ->selectRaw('student_id, MAX(COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at)) as attempt_activity_at')
            ->groupBy('student_id');
        $activitySql = 'CASE
            WHEN users.last_active_at IS NULL THEN attempt_activity.attempt_activity_at
            WHEN attempt_activity.attempt_activity_at IS NULL THEN users.last_active_at
            WHEN users.last_active_at >= attempt_activity.attempt_activity_at THEN users.last_active_at
            ELSE attempt_activity.attempt_activity_at
        END';

        $relations = TeacherStudent::where('teacher_id', $teacherId)
            ->get(['student_id', 'student_alias', 'evrium_name'])
            ->keyBy('student_id');

        return User::where('users.role', 'student')
            ->leftJoinSub($attemptActivity, 'attempt_activity', function ($join) {
                $join->on('attempt_activity.student_id', '=', 'users.id');
            })
            ->select(['users.id', 'users.name', 'users.email', 'users.last_active_at'])
            ->selectRaw($activitySql . ' as activity_at')
            ->orderByRaw('COALESCE(' . $activitySql . ', users.created_at) DESC')
            ->orderBy('users.name')
            ->limit(2000)
            ->get()
            ->map(function (User $student) use ($relations) {
                $relation = $relations->get($student->id);
                $activityAt = $student->activity_at ? Carbon::parse($student->activity_at) : null;

                return [
                    'id' => (int) $student->id,
                    'name' => $student->name ?: 'Ученик #' . $student->id,
                    'email' => $student->email,
                    'last_active_at' => $activityAt?->toIso8601String(),
                    'last_active_label' => $activityAt ? $activityAt->format('d.m H:i') . ' · ' . $activityAt->diffForHumans(short: true) : 'нет активности',
                    'linked_evrium_name' => $relation?->evrium_name,
                    'student_alias' => $relation?->student_alias,
                    'is_linked_to_teacher' => $relation !== null,
                ];
            })
            ->values()
            ->all();
    }

    // ---- Schedule helpers (copied from MiniAppTeacherController) ----

    /**
     * Расписание учителя из Evrium. teacher_id берётся из users.evrium_teacher_id —
     * каждый учитель видит ТОЛЬКО своё расписание. Не привязанный к Evrium учитель
     * (evrium_teacher_id = null) получает пустой список, а не чужие уроки.
     */
    protected function fetchEvriumSchedule(?int $evriumTeacherId): array
    {
        if (app()->environment('testing')) return [];
        if (empty($evriumTeacherId)) return [];

        $apiUrl = 'https://xn--b1ammoq0d.xn--p1ai/zarplata/api/external.php';
        $apiKey = '15da8c6b7eed43fd5f3afae70a9f792516fb49957127e2ae';

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders(['X-Api-Key' => $apiKey])->timeout(5)->get($apiUrl, ['action' => 'schedule', 'teacher_id' => $evriumTeacherId]);
            if (!$response->ok()) return [];
            return $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function resolveEvriumSlots(array $slots, \Illuminate\Support\Collection $relations, ?string $lessonDate = null): array
    {
        $evriumMap = [];
        foreach ($relations as $rel) {
            if ($rel->evrium_name) {
                $evriumMap[$rel->evrium_name][] = $rel;
            }
        }

        $result = [];
        foreach ($slots as $slot) {
            foreach ($slot['students'] ?? [] as $evriumName) {
                $linkedRelations = collect($evriumMap[$evriumName] ?? []);
                $profiles = $linkedRelations->map(function ($rel) {
                    return [
                        'student_id' => (int) $rel->student_id,
                        'student_name' => $rel->student?->name ?? 'Ученик #' . $rel->student_id,
                        'student_alias' => $rel->student_alias,
                        'last_active_at' => $rel->student?->last_active_at,
                    ];
                })->values()->all();
                $first = $linkedRelations->first();

                $result[] = [
                    'evrium_name' => $evriumName,
                    'time_start' => $slot['time_start'] ?? '',
                    'time_end' => $slot['time_end'] ?? '',
                    'lesson_date' => $lessonDate,
                    'student_id' => $first?->student_id,
                    'student_ids' => $linkedRelations->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all(),
                    'student_name' => $first ? ($first->student?->name ?? 'Ученик #' . $first->student_id) : null,
                    'student_alias' => $first?->student_alias,
                    'linked_profiles' => $profiles,
                    'linked' => $linkedRelations->isNotEmpty(),
                ];
            }
        }
        return $result;
    }

    protected function buildTodayLessonSlots(array $slots, \Illuminate\Support\Collection $relations): array
    {
        $evriumMap = [];
        foreach ($relations as $rel) {
            if ($rel->evrium_name) {
                $evriumMap[$rel->evrium_name][] = $rel;
            }
        }

        $result = [];
        foreach ($slots as $slot) {
            $students = [];
            foreach ($slot['students'] ?? [] as $evriumName) {
                $linkedRelations = collect($evriumMap[$evriumName] ?? []);
                $first = $linkedRelations->first();
                $student = $first?->student;
                $students[] = [
                    'evrium_name' => $evriumName,
                    'student_id' => $first?->student_id,
                    'student_ids' => $linkedRelations->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all(),
                    'student_name' => $first ? ($first->student_alias ?: ($student?->name ?: 'Ученик #' . $first->student_id)) : $evriumName,
                    'student_full_name' => $student?->name,
                    'student_alias' => $first?->student_alias,
                    'linked_profiles' => $linkedRelations->map(fn ($rel) => [
                        'student_id' => (int) $rel->student_id,
                        'student_name' => $rel->student_alias ?: ($rel->student?->name ?? 'Ученик #' . $rel->student_id),
                        'student_full_name' => $rel->student?->name,
                    ])->values()->all(),
                    'linked' => $linkedRelations->isNotEmpty(),
                ];
            }
            $status = $this->determineLessonStatus((string) ($slot['time_start'] ?? ''), (string) ($slot['time_end'] ?? ''));
            $result[] = ['time_start' => (string) ($slot['time_start'] ?? ''), 'time_end' => (string) ($slot['time_end'] ?? ''), 'status_key' => $status['key'], 'status_label' => $status['label'], 'students' => $students];
        }

        usort($result, fn($a, $b) => strcmp($a['time_start'], $b['time_start']));
        return $result;
    }

    protected function determineLessonStatus(string $timeStart, string $timeEnd): array
    {
        if ($timeStart === '') return ['key' => 'upcoming', 'label' => 'будет'];
        $now = now();
        $start = now()->startOfDay();
        [$hours, $minutes] = array_pad(explode(':', $timeStart), 2, '0');
        $start = $start->copy()->setTime((int) $hours, (int) $minutes);
        $end = $timeEnd !== '' ? now()->startOfDay()->setTime(...array_map('intval', array_pad(explode(':', $timeEnd), 2, '0'))) : $start->copy()->addMinutes(60);

        if ($now->lt($start)) return ['key' => 'upcoming', 'label' => 'будет'];
        if ($now->between($start, $end)) return ['key' => 'current', 'label' => 'идёт'];
        return ['key' => 'past', 'label' => 'прошёл'];
    }

    /**
     * Расписание уроков на сегодня + ближайшие дни (по недельным слотам Evrium),
     * спроецированное на календарные даты. Каждый слот получает starts_at/ends_at
     * и, если для этого времени уже есть draft/live сессия, её id+status —
     * чтобы карточка вела прямо в подготовленный урок.
     *
     * Дни без слотов пропускаются. Возвращает по одному вхождению на каждый
     * день в окне [сегодня; сегодня+$daysAhead].
     *
     * @return array<int, array{date:string,label:string,is_today:bool,slots:array}>
     */
    protected function buildUpcomingLessonDays(User $user, int $daysAhead = 6): array
    {
        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

        $relations   = TeacherStudent::where('teacher_id', $user->id)->with('student:id,name,last_active_at')->get();
        $evriumSlots = $this->fetchEvriumSchedule($user->evrium_teacher_id);

        // Уже существующие черновики/идущие уроки в окне — для прямой ссылки из карточки.
        $windowSessions = LessonSession::where('teacher_id', $user->id)
            ->whereIn('status', [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE])
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [now()->startOfDay(), now()->copy()->addDays($daysAhead)->endOfDay()])
            ->with('participants.student:id,name')
            ->get();
        $sessionsByStart = $windowSessions->keyBy(fn ($s) => $s->starts_at->format('Y-m-d H:i'));
        $matchedSessionIds = [];

        $days = [];
        for ($offset = 0; $offset <= $daysAhead; $offset++) {
            $date    = now()->copy()->addDays($offset)->startOfDay();
            $dow     = (int) $date->format('N');
            $isToday = $offset === 0;

            $daySlots     = array_values(array_filter($evriumSlots, fn ($s) => ($s['day'] ?? 0) === $dow));
            $daySessions  = $windowSessions->filter(fn ($s) => $s->starts_at->isSameDay($date));
            if (empty($daySlots) && $daySessions->isEmpty()) {
                continue;
            }

            $slots = [];
            foreach ($this->buildTodayLessonSlots($daySlots, $relations) as $slot) {
                $timeStart  = (string) $slot['time_start'];
                $timeEnd    = (string) $slot['time_end'];
                $startsAt   = $timeStart !== '' ? $date->copy()->setTimeFromTimeString($timeStart) : null;
                $endsAt     = ($timeEnd !== '' && $startsAt) ? $date->copy()->setTimeFromTimeString($timeEnd) : null;
                $studentIds = collect($slot['students'])
                    ->flatMap(fn ($s) => $s['student_ids'] ?? [])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                // Для будущих дней статус всегда «будет»; для сегодня — реальный (прошёл/идёт/будет).
                $status  = $isToday ? ['key' => $slot['status_key'], 'label' => $slot['status_label']] : ['key' => 'upcoming', 'label' => 'будет'];
                $session = $startsAt ? $sessionsByStart->get($startsAt->format('Y-m-d H:i')) : null;
                if ($session) {
                    $matchedSessionIds[] = $session->id;
                }

                $slots[] = [
                    'time_start'     => $timeStart,
                    'time_end'       => $timeEnd,
                    'status_key'     => $status['key'],
                    'status_label'   => $status['label'],
                    'students'       => $slot['students'],
                    'student_ids'    => $studentIds,
                    'starts_at'      => $startsAt?->format('Y-m-d H:i:s'),
                    'ends_at'        => $endsAt?->format('Y-m-d H:i:s'),
                    'session_id'     => $session?->id,
                    'session_status' => $session?->status,
                    'is_adhoc'       => false,
                    'note'           => null,
                ];
            }

            // Внеплановые уроки (созданные «Начать новый урок» / «Следующий урок»),
            // не совпавшие по времени ни с одним Evrium-слотом.
            foreach ($daySessions as $s) {
                if (in_array($s->id, $matchedSessionIds, true)) {
                    continue;
                }
                $slots[] = [
                    'time_start'     => $s->starts_at->format('H:i'),
                    'time_end'       => $s->ends_at?->format('H:i') ?? '',
                    'status_key'     => $s->status === LessonSession::STATUS_LIVE ? 'current' : 'upcoming',
                    'status_label'   => $s->status === LessonSession::STATUS_LIVE ? 'идёт' : 'будет',
                    'students'       => $s->participants->map(fn ($p) => [
                        'student_id'        => $p->student_id,
                        'student_name'      => $p->student?->name ?? ('#' . $p->student_id),
                        'student_full_name' => '',
                        'evrium_name'       => '',
                    ])->values()->all(),
                    'student_ids'    => [],
                    'starts_at'      => $s->starts_at->format('Y-m-d H:i:s'),
                    'ends_at'        => $s->ends_at?->format('Y-m-d H:i:s'),
                    'session_id'     => $s->id,
                    'session_status' => $s->status,
                    'is_adhoc'       => true,
                    'note'           => $s->note,
                ];
            }

            usort($slots, fn ($a, $b) => strcmp($a['time_start'], $b['time_start']));

            $prefix = $isToday ? 'Сегодня' : ($offset === 1 ? 'Завтра' : null);
            $label  = trim(($prefix ? $prefix . ' · ' : '') . $dayNames[$dow] . ' ' . $date->format('d.m'));

            $days[] = [
                'date'     => $date->toDateString(),
                'label'    => $label,
                'is_today' => $isToday,
                'slots'    => $slots,
            ];
        }

        return $days;
    }

    protected function collectTeacherScheduleData(User $user): array
    {
        $dow = (int) now()->format('N');
        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
        $todayDate = now()->toDateString();

        $relations = TeacherStudent::where('teacher_id', $user->id)->with('student:id,name,last_active_at')->get();
        $evriumSlots = $this->fetchEvriumSchedule($user->evrium_teacher_id);
        $todayEvrium = array_filter($evriumSlots, fn($s) => ($s['day'] ?? 0) === $dow);
        $todayLessons = $this->buildTodayLessonSlots($todayEvrium, $relations);
        $currentStudents = $this->resolveEvriumSlots($todayEvrium, $relations, $todayDate);

        $prevStudents = [];
        $prevDayLabel = '';
        $prevDate = null;
        for ($offset = 1; $offset <= 7; $offset++) {
            $checkDow = (($dow - 1 - $offset % 7) + 7) % 7 + 1;
            $daySlots = array_filter($evriumSlots, fn($s) => ($s['day'] ?? 0) === $checkDow);
            if (!empty($daySlots)) {
                $prevDate = now()->subDays($offset)->toDateString();
                $prevStudents = $this->resolveEvriumSlots($daySlots, $relations, $prevDate);
                $prevDayLabel = $dayNames[$checkDow];
                break;
            }
        }

        return [
            'relations' => $relations,
            'evriumSlots' => $evriumSlots,
            'todayLessons' => $todayLessons,
            'currentStudents' => $currentStudents,
            'prevStudents' => $prevStudents,
            'prevDayLabel' => $prevDayLabel,
            'prevDate' => $prevDate,
            'todayLabel' => $dayNames[$dow],
            'todayDate' => $todayDate,
        ];
    }
}
