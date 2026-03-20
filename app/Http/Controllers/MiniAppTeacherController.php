<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\HomeworkAssignment;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\VariantTaskNumberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniAppTeacherController extends Controller
{
    use MiniAppHelpers;

    public function __construct(
        private readonly TaskDataService $taskData,
        private readonly OgeVariantPoolService $poolService,
    ) {
    }

    public function teacherDashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $studentCount = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->count();

        $aliasedCount = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('student_alias')
            ->where('student_alias', '!=', '')
            ->count();

        $variantsCount = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->count();

        $curatedCount = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->where('is_curated', true)
            ->count();

        $recentVariants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'hash', 'title', 'mode', 'is_curated', 'created_at']);

        // Recent student attempts — for quick access during lessons
        $myStudentIds = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->pluck('student_id');

        $aliasMap = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('student_alias')
            ->where('student_alias', '!=', '')
            ->pluck('student_alias', 'student_id');

        $recentAttempts = [];
        if ($myStudentIds->isNotEmpty()) {
            $recentAttempts = OgeAttempt::query()
                ->whereIn('student_id', $myStudentIds)
                ->whereIn('status', ['submitted', 'scored'])
                ->with([
                    'variant:id,hash,title,mode',
                    'student:id,name',
                    'scorings:id,attempt_id,is_correct',
                ])
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
                })
                ->all();
        }

        return view('miniapp.teacher-dashboard', [
            'user' => $user,
            'studentCount' => $studentCount,
            'aliasedCount' => $aliasedCount,
            'variantsCount' => $variantsCount,
            'curatedCount' => $curatedCount,
            'recentVariants' => $recentVariants,
            'recentAttempts' => $recentAttempts,
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
            'canSwitchMode' => $user->role === 'admin',
        ]);
    }

    public function teacherLessons(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $scheduleData = $this->collectTeacherScheduleData($user);

        return view('miniapp.teacher-lessons', [
            'todayLabel' => $scheduleData['todayLabel'],
            'todayLessons' => $scheduleData['todayLessons'],
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
            'canSwitchMode' => $user->role === 'admin',
        ]);
    }

    public function teacherStudents(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;
        $search = trim((string) $request->query('search', ''));
        $filter = trim((string) $request->query('filter', 'all'));
        $scheduleData = $this->collectTeacherScheduleData($user);
        $scheduledStudentIds = collect($scheduleData['currentStudents'])
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Show all active students who use app/solve variants, with teacher-specific ownership marker.
        // Use scalar subqueries to avoid duplicates when multiple teacher_students rows exist.
        $studentsQuery = User::query()
            ->where('users.role', 'student')
            ->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('oge_attempts')
                        ->whereColumn('oge_attempts.student_id', 'users.id');
                })->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('teacher_students')
                        ->whereColumn('teacher_students.student_id', 'users.id');
                });
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.avatar',
                'users.last_active_at',
            ])
            ->selectRaw(
                '(SELECT ts.student_alias FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as student_alias',
                [$teacherId]
            )
            ->selectRaw(
                '(SELECT ts.created_at FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as linked_at',
                [$teacherId]
            )
            ->selectRaw(
                '(SELECT ts.evrium_name FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as evrium_name',
                [$teacherId]
            )
            ->selectRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id) THEN 1 ELSE 0 END as is_mine',
                [$teacherId]
            )
            ->when($search !== '', function ($query) use ($search, $teacherId) {
                $query->where(function ($nested) use ($search, $teacherId) {
                    $nested->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%')
                        ->orWhereRaw(
                            'EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id AND ts.student_alias like ?)',
                            [$teacherId, '%' . $search . '%']
                        );
                });
            });

        if ($filter === 'mine') {
            $studentsQuery->whereExists(function ($sub) use ($teacherId) {
                $sub->selectRaw('1')
                    ->from('teacher_students')
                    ->whereColumn('teacher_students.student_id', 'users.id')
                    ->where('teacher_students.teacher_id', $teacherId);
            });
        } elseif ($filter === 'scheduled') {
            if ($scheduledStudentIds->isNotEmpty()) {
                $studentsQuery->whereIn('users.id', $scheduledStudentIds);
            } else {
                $studentsQuery->whereRaw('1 = 0');
            }
        } elseif ($filter === 'risk') {
            $studentsQuery->where(function ($query) use ($teacherId) {
                $query->whereNull('users.last_active_at')
                    ->orWhere('users.last_active_at', '<', now()->subDays(7))
                    ->orWhereRaw(
                        'EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id AND (ts.student_alias IS NULL OR ts.student_alias = "" OR ts.evrium_name IS NULL OR ts.evrium_name = ""))',
                        [$teacherId]
                    );
            });
        } elseif ($filter === 'unlinked') {
            $studentsQuery->whereRaw(
                'EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id AND (ts.evrium_name IS NULL OR ts.evrium_name = ""))',
                [$teacherId]
            );
        }

        $students = $studentsQuery
            ->orderByRaw('COALESCE(users.last_active_at, users.created_at) DESC')
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        $students->setCollection(
            $students->getCollection()->map(function (User $student) use ($scheduledStudentIds) {
                $student->is_scheduled_today = $scheduledStudentIds->contains((int) $student->id);
                $student->risk_label = null;
                $student->risk_tone = 'accent';

                if (!(bool) ($student->is_mine ?? false)) {
                    $student->risk_label = 'Не привязан';
                    $student->risk_tone = 'red';
                } elseif (blank($student->evrium_name)) {
                    $student->risk_label = 'Без расписания';
                    $student->risk_tone = 'red';
                } elseif (blank($student->student_alias)) {
                    $student->risk_label = 'Без алиаса';
                    $student->risk_tone = 'yellow';
                } elseif (!$student->last_active_at || $student->last_active_at->lt(now()->subDays(7))) {
                    $student->risk_label = 'Есть риск';
                    $student->risk_tone = 'yellow';
                } else {
                    $student->risk_label = 'В порядке';
                    $student->risk_tone = 'green';
                }

                return $student;
            })
        );

        return view('miniapp.teacher-students', [
            'students' => $students,
            'search' => $search,
            'filter' => $filter,
            'canSwitchMode' => $user->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
        ]);
    }

    public function teacherVariants(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $variants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount('attempts')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('miniapp.teacher-variants', [
            'variants' => $variants,
            'canSwitchMode' => $user->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
        ]);
    }

    public function teacherReferrals(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        // Top referrers: users who invited the most people
        $referrers = User::query()
            ->whereHas('referrals')
            ->withCount('referrals')
            ->orderByDesc('referrals_count')
            ->limit(100)
            ->get(['id', 'name', 'role', 'created_at']);

        $totalUsers = User::count();
        $totalReferred = User::whereNotNull('referred_by_user_id')->count();

        // Recent referrals with who invited whom
        $recentReferrals = User::query()
            ->whereNotNull('referred_by_user_id')
            ->with('referrer:id,name,role')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'name', 'role', 'referred_by_user_id', 'created_at']);

        return view('miniapp.teacher-referrals', [
            'referrers' => $referrers,
            'totalUsers' => $totalUsers,
            'totalReferred' => $totalReferred,
            'recentReferrals' => $recentReferrals,
            'canSwitchMode' => $request->user()->role === 'admin',
        ]);
    }

    public function teacherStudentProfile(Request $request, int $studentId)
    {
        /** @var User $teacher */
        $teacher = $request->user();

        $student = User::query()
            ->where('role', 'student')
            ->findOrFail($studentId);

        $teacherRelation = TeacherStudent::query()
            ->where('teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->first();

        $attempts = OgeAttempt::query()
            ->where('student_id', $student->id)
            ->with([
                'variant:id,hash,title,mode',
                'scorings:id,attempt_id,task_number,is_correct',
            ])
            // Show full student history in miniapp teacher profile (not only variants created by current teacher).
            ->orderByRaw('COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $topicStats = [];
        $correctTotal = 0;
        $scoredTotal = 0;

        foreach ($attempts as $attempt) {
            foreach ($attempt->scorings as $scoring) {
                if ($scoring->is_correct === null) {
                    continue;
                }
                $taskNum = (int) $scoring->task_number;
                if (!isset($topicStats[$taskNum])) {
                    $topicStats[$taskNum] = ['task_number' => $taskNum, 'correct' => 0, 'total' => 0];
                }
                $topicStats[$taskNum]['total']++;
                $scoredTotal++;
                if ((bool) $scoring->is_correct) {
                    $topicStats[$taskNum]['correct']++;
                    $correctTotal++;
                }
            }
        }

        usort($topicStats, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        // Build variant history list (same format as student history page)
        $historyList = [];
        foreach ($attempts as $att) {
            if (!in_array($att->status, ['submitted', 'scored'])) {
                continue;
            }
            $correct = $att->scorings->where('is_correct', true)->count();
            $total = $att->scorings->count();
            $time = null;
            if ($att->started_at && $att->submitted_at) {
                $time = $att->submitted_at->diffInSeconds($att->started_at);
            }
            $historyList[] = [
                'id' => $att->id,
                'label' => $this->variantModeLabel($att->variant),
                'hash' => $att->variant->hash ?? null,
                'correct' => $correct,
                'total' => $total,
                'time' => $time,
                'date' => $att->submitted_at,
            ];
        }

        $weakTopics = collect($topicStats)
            ->map(function (array $topic) {
                $accuracy = $topic['total'] > 0 ? (int) round(($topic['correct'] / $topic['total']) * 100) : 0;
                return $topic + [
                    'accuracy' => $accuracy,
                    'tone' => $accuracy >= 70 ? 'green' : ($accuracy >= 40 ? 'yellow' : 'red'),
                ];
            })
            ->sortBy([
                ['accuracy', 'asc'],
                ['total', 'desc'],
            ])
            ->take(5)
            ->values();

        $homeworkHistory = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('homework_assignments') && \Illuminate\Support\Facades\Schema::hasTable('homeworks')) {
            $homeworkHistory = HomeworkAssignment::query()
                ->where('student_id', $student->id)
                ->with('homework:id,title,homework_type,topic_number,assigned_at')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->map(function (HomeworkAssignment $assignment) {
                    $homework = $assignment->homework;
                    return [
                        'title' => $homework?->title ?: 'Домашнее задание',
                        'subtitle' => $homework?->assigned_at?->format('d.m.Y H:i') ?: 'Без даты',
                        'status' => $assignment->status ?: 'assigned',
                    ];
                });
        }

        return view('miniapp.teacher-student-profile', [
            'student' => $student,
            'teacherRelation' => $teacherRelation,
            'attempts' => $attempts,
            'topicStats' => $topicStats,
            'weakTopics' => $weakTopics,
            'correctTotal' => $correctTotal,
            'scoredTotal' => $scoredTotal,
            'accuracy' => $scoredTotal > 0 ? (int) round(($correctTotal / $scoredTotal) * 100) : null,
            'historyList' => $historyList,
            'homeworkHistory' => $homeworkHistory,
            'canSwitchMode' => $teacher->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $teacher),
        ]);
    }

    public function teacherStudentAttemptDetail(Request $request, int $studentId, int $attemptId)
    {
        $student = User::where('role', 'student')->findOrFail($studentId);

        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with([
                'variant:id,hash,title,mode,config_json',
                'answers:id,attempt_id,task_number,current_answer',
                'scorings:id,attempt_id,task_number,is_correct,correct_answer',
            ])
            ->firstOrFail();

        $cfg = $attempt->variant?->config_json;
        $configTasks = (is_array($cfg) && isset($cfg['tasks']) && is_array($cfg['tasks'])) ? $cfg['tasks'] : [];

        $correct = $attempt->scorings->where('is_correct', true)->count();
        $total = count($configTasks) ?: $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        $taskMap = [];
        if ($attempt->variant && !empty($configTasks)) {
            $resolved = VariantTaskNumberResolver::resolveAll($configTasks, $attempt->variant);
            foreach ($resolved as $entry) {
                $taskMap[$entry['slot']] = $entry['task'];
            }
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
                $normI = preg_replace('/\s+/u', ' ', mb_strtolower($instructionText));
                $normC = preg_replace('/\s+/u', ' ', mb_strtolower($conditionText));
                if ($normI === $normC) $instructionText = '';
            }

            $taskText = $conditionText !== '' ? $conditionText : $instructionText;
            $taskExpression = (string) (($def['expression'] ?? $inner['expression'] ?? $inner['formula'] ?? $inner['latex'] ?? '') ?: '');

            $rawOptions = $def['options'] ?? $inner['options'] ?? $def['variants'] ?? $inner['variants'] ?? null;
            $taskOptions = [];
            if (is_array($rawOptions)) {
                $taskOptions = array_values($rawOptions);
            } elseif (is_string($rawOptions) && trim($rawOptions) !== '') {
                $taskOptions = array_values(array_filter(array_map('trim', preg_split('/\R+/', $rawOptions))));
            }

            $wrongTasks[] = [
                'task_number' => $taskNum,
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

        usort($wrongTasks, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        $label = $this->variantModeLabel($attempt->variant);
        $backUrl = "/tg/teacher/students/{$studentId}";

        return view('miniapp.history-detail', compact('attempt', 'label', 'correct', 'total', 'time', 'wrongTasks', 'backUrl'));
    }

    public function toggleTeacherStudentOwnership(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $student = User::query()->where('id', $studentId)->where('role', 'student')->firstOrFail();

        $relation = TeacherStudent::query()
            ->where('teacher_id', $user->id)
            ->where('student_id', $student->id)
            ->first();

        if ($relation) {
            $relation->delete();
            $isMine = false;
            $event = 'teacher_student_unlinked';
        } else {
            TeacherStudent::query()->create([
                'teacher_id' => $user->id,
                'student_id' => $student->id,
                // `teacher_students.source` enum: referral|manual|homework_invite
                'source' => 'manual',
            ]);
            $isMine = true;
            $event = 'teacher_student_linked';
        }

        $audit->log([
            'event_type' => $event,
            'category' => 'teacher',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $this->resolveMiniAppRole($request, $user),
            'subject_type' => 'teacher_student',
            'subject_id' => $user->id . ':' . $student->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'is_mine' => $isMine,
                'source' => 'miniapp',
            ],
        ]);

        return response()->json([
            'success' => true,
            'is_mine' => $isMine,
        ]);
    }

    public function updateTeacherStudentAlias(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $request->validate([
            'alias' => 'nullable|string|max:80',
        ]);

        $alias = trim((string) ($payload['alias'] ?? ''));
        $alias = $alias === '' ? null : $alias;

        $relation = TeacherStudent::query()
            ->where('teacher_id', $user->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $previousAlias = $relation->student_alias;
        $relation->student_alias = $alias;
        $relation->save();

        $audit->log([
            'event_type' => 'teacher_student_alias_updated',
            'category' => 'teacher',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $this->resolveMiniAppRole($request, $user),
            'subject_type' => 'teacher_student',
            'subject_id' => $user->id . ':' . $studentId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'previous_alias' => $previousAlias,
                'new_alias' => $alias,
            ],
        ]);

        return response()->json([
            'success' => true,
            'alias' => $alias,
        ]);
    }

    /**
     * Teacher homework page — list today's students and assigned homework.
     */
    public function teacherHomework(Request $request)
    {
        $user = $request->user();
        $scheduleData = $this->collectTeacherScheduleData($user);
        $relations = $scheduleData['relations'];
        $evriumSlots = $scheduleData['evriumSlots'];
        $currentStudents = $scheduleData['currentStudents'];
        $prevStudents = $scheduleData['prevStudents'];
        $prevDayLabel = $scheduleData['prevDayLabel'];

        // All teacher's students (from Palomatika DB)
        $allStudentIds = $relations->pluck('student_id');
        $allStudents = User::whereIn('id', $allStudentIds)->select('id', 'name')->orderBy('name')->get();

        // Attach evrium_name to allStudents for display
        $relMap = $relations->keyBy('student_id');
        foreach ($allStudents as $s) {
            $s->evrium_name = $relMap[$s->id]->evrium_name ?? null;
            $s->student_alias = $relMap[$s->id]->student_alias ?? null;
        }

        // All unique Evrium student names for the linking dropdown
        $allEvriumNames = collect($evriumSlots)
            ->pluck('students')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Recent homework
        $recentHomework = \App\Models\Homework::where('teacher_id', $user->id)
            ->whereIn('homework_type', ['full_variant', 'topic_practice'])
            ->orderByDesc('assigned_at')
            ->limit(30)
            ->get();
        $recentHomework->load('assignments.student:id,name');

        return view('miniapp.teacher-homework', compact(
            'user', 'currentStudents', 'prevStudents', 'prevDayLabel',
            'allStudents', 'allEvriumNames', 'recentHomework'
        ) + ['todayLabel' => $scheduleData['todayLabel']]);
    }

    /**
     * Update evrium_name and/or alias for a teacher's student (PATCH).
     */
    public function updateStudentLink(Request $request, int $studentId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'evrium_name' => 'nullable|string|max:100',
            'alias' => 'nullable|string|max:80',
        ]);

        $relation = TeacherStudent::where('teacher_id', $user->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

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

    /**
     * Assign homework to a student (POST).
     */
    public function assignHomework(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'type' => 'required|in:full_variant,topic_practice',
            'topic_number' => 'required_if:type,topic_practice|nullable|integer',
        ]);

        $studentId = (int) $data['student_id'];

        $isTeacher = TeacherStudent::where('teacher_id', $user->id)
            ->where('student_id', $studentId)
            ->exists();

        if (!$isTeacher) {
            return back()->with('error', 'Ученик не найден.');
        }

        $homework = new \App\Models\Homework();
        $homework->teacher_id = $user->id;
        $homework->homework_type = $data['type'];

        if ($data['type'] === 'full_variant') {
            $student = User::findOrFail($studentId);
            try {
                $variant = $this->poolService->getOrCreateVariant($student, 'full');
            } catch (\RuntimeException $e) {
                return back()->with('error', 'Не удалось создать вариант: ' . $e->getMessage());
            }
            $homework->variant_hash = $variant->hash;
            $homework->title = 'Полный вариант ОГЭ';
        } else {
            $topicNumber = (int) $data['topic_number'];
            $homework->topic_number = $topicNumber;
            $homework->title = 'Тема ' . $topicNumber;
        }

        $homework->assigned_at = now();
        $homework->save();

        \App\Models\HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $studentId,
            'status' => 'assigned',
        ]);

        return back()->with('success', 'ДЗ выдано!');
    }

    /**
     * Fetch Evrium schedule for teacher and resolve Palomatika students via evrium_name mapping.
     */
    protected function fetchEvriumSchedule(int $teacherId, int $evriumTeacherId = 1): array
    {
        if (app()->environment('testing')) {
            return [];
        }

        $apiUrl = 'https://xn--b1ammoq0d.xn--p1ai/zarplata/api/external.php';
        $apiKey = '15da8c6b7eed43fd5f3afae70a9f792516fb49957127e2ae';

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(5)
                ->get($apiUrl, ['action' => 'schedule', 'teacher_id' => $evriumTeacherId]);

            if (!$response->ok()) return [];
            return $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Build schedule-based student list from Evrium slots, matched to Palomatika users.
     */
    protected function resolveEvriumSlots(array $slots, \Illuminate\Support\Collection $relations): array
    {
        // Build evrium_name → palomatika relation map
        $evriumMap = [];
        foreach ($relations as $rel) {
            if ($rel->evrium_name) {
                $evriumMap[$rel->evrium_name] = $rel;
            }
        }

        $result = [];
        foreach ($slots as $slot) {
            foreach ($slot['students'] ?? [] as $evriumName) {
                $rel = $evriumMap[$evriumName] ?? null;
                $result[] = [
                    'evrium_name' => $evriumName,
                    'time_start' => $slot['time_start'] ?? '',
                    'time_end' => $slot['time_end'] ?? '',
                    'student_id' => $rel?->student_id,
                    'student_name' => $rel ? ($rel->student?->name ?? 'Ученик #' . $rel->student_id) : null,
                    'student_alias' => $rel?->student_alias,
                    'linked' => $rel !== null,
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
                $evriumMap[$rel->evrium_name] = $rel;
            }
        }

        $result = [];
        foreach ($slots as $slot) {
            $students = [];
            foreach ($slot['students'] ?? [] as $evriumName) {
                $rel = $evriumMap[$evriumName] ?? null;
                $student = $rel?->student;
                $students[] = [
                    'evrium_name' => $evriumName,
                    'student_id' => $rel?->student_id,
                    'student_name' => $rel ? ($rel->student_alias ?: ($student?->name ?: 'Ученик #' . $rel->student_id)) : $evriumName,
                    'student_full_name' => $student?->name,
                    'student_alias' => $rel?->student_alias,
                    'linked' => $rel !== null,
                    'evrium_linked' => $rel?->evrium_name,
                    'risk_label' => $rel === null
                        ? 'Не привязан'
                        : (blank($rel->student_alias) ? 'Без алиаса' : ((!$student?->last_active_at || $student->last_active_at->lt(now()->subDays(7))) ? 'Есть риск' : 'В порядке')),
                    'risk_tone' => $rel === null
                        ? 'red'
                        : (blank($rel->student_alias) ? 'yellow' : ((!$student?->last_active_at || $student->last_active_at->lt(now()->subDays(7))) ? 'yellow' : 'green')),
                ];
            }

            $status = $this->determineLessonStatus(
                (string) ($slot['time_start'] ?? ''),
                (string) ($slot['time_end'] ?? '')
            );

            $result[] = [
                'time_start' => (string) ($slot['time_start'] ?? ''),
                'time_end' => (string) ($slot['time_end'] ?? ''),
                'status_key' => $status['key'],
                'status_label' => $status['label'],
                'students' => $students,
            ];
        }

        usort($result, fn ($a, $b) => strcmp($a['time_start'], $b['time_start']));

        return $result;
    }

    protected function determineLessonStatus(string $timeStart, string $timeEnd): array
    {
        if ($timeStart === '') {
            return ['key' => 'upcoming', 'label' => 'будет'];
        }

        $now = now();
        $start = now()->startOfDay();
        [$hours, $minutes] = array_pad(explode(':', $timeStart), 2, '0');
        $start = $start->copy()->setTime((int) $hours, (int) $minutes);

        $end = $timeEnd !== '' ? now()->startOfDay()->setTime(...array_map('intval', array_pad(explode(':', $timeEnd), 2, '0'))) : $start->copy()->addMinutes(60);

        if ($now->lt($start)) {
            return ['key' => 'upcoming', 'label' => 'будет'];
        }

        if ($now->between($start, $end)) {
            return ['key' => 'current', 'label' => 'идёт'];
        }

        return ['key' => 'past', 'label' => 'прошёл'];
    }

    protected function collectTeacherScheduleData(User $user): array
    {
        $dow = (int) now()->format('N');
        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

        $relations = TeacherStudent::where('teacher_id', $user->id)
            ->with('student:id,name,last_active_at')
            ->get();

        $evriumSlots = $this->fetchEvriumSchedule($user->id);
        $todayEvrium = array_filter($evriumSlots, fn($s) => ($s['day'] ?? 0) === $dow);
        $todayLessons = $this->buildTodayLessonSlots($todayEvrium, $relations);
        $currentStudents = $this->resolveEvriumSlots($todayEvrium, $relations);

        $featuredLesson = collect($todayLessons)->firstWhere('status_key', 'current')
            ?? collect($todayLessons)->firstWhere('status_key', 'upcoming')
            ?? ($todayLessons[0] ?? null);

        $prevStudents = [];
        $prevDayLabel = '';
        for ($offset = 1; $offset <= 7; $offset++) {
            $checkDow = (($dow - 1 - $offset % 7) + 7) % 7 + 1;
            $daySlots = array_filter($evriumSlots, fn($s) => ($s['day'] ?? 0) === $checkDow);
            if (!empty($daySlots)) {
                $prevStudents = $this->resolveEvriumSlots($daySlots, $relations);
                $prevDayLabel = $dayNames[$checkDow];
                break;
            }
        }

        return [
            'relations' => $relations,
            'evriumSlots' => $evriumSlots,
            'todayLessons' => $todayLessons,
            'featuredLesson' => $featuredLesson,
            'currentStudents' => $currentStudents,
            'prevStudents' => $prevStudents,
            'prevDayLabel' => $prevDayLabel,
            'todayLabel' => $dayNames[$dow],
        ];
    }
}
