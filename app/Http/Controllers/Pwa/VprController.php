<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Services\OgeAttemptService;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VprController extends Controller
{
    private function makePool(int $grade): VprVariantPoolService
    {
        $taskData = new VprTaskDataService($grade);
        $builder  = new VprVariantBuilderService($taskData);
        return new VprVariantPoolService($taskData, $builder);
    }

    /**
     * ВПР-дашборд (home page).
     */
    public function home(Request $request)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

        $examType = 'vpr_' . $grade;
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->whereHas('variant', fn($q) => $q->where('exam_type', $examType))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeList = $activeAttempts->map(function ($att) {
            $variant = $att->variant;
            return [
                'id'            => $att->id,
                'title'         => $variant->title ?? 'Вариант ВПР',
                'answeredCount' => $att->answers()->count(),
                'totalCount'    => count($variant->config_json['tasks'] ?? []),
                'startedAt'     => $att->started_at,
            ];
        })->all();

        return view('pwa.student.vpr-home', compact('user', 'grade', 'activeList'));
    }

    /**
     * Старт нового варианта ВПР (POST).
     */
    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

        if ($grade < 5 || $grade > 8) {
            abort(403, 'ВПР доступно только для 5–8 классов');
        }

        $pool    = $this->makePool($grade);
        $variant = $pool->getOrCreateVariant($user);

        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        return redirect()->route('pwa.student.vpr.test', $attempt->id);
    }

    /**
     * Страница решения варианта ВПР.
     */
    public function test(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->firstOrFail();

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect()->route('pwa.student.vpr.results', $attempt->id);
        }

        $variant = $attempt->variant;
        $tasks   = $variant->config_json['tasks'] ?? [];
        $answers = $attempt->answers()->pluck('current_answer', 'task_number');
        $mode    = $variant->mode ?? 'full';
        $title   = $variant->title ?? 'Вариант ВПР';

        return view('pwa.student.vpr-test', compact(
            'attempt', 'tasks', 'answers', 'mode', 'title'
        ));
    }

    /**
     * База заданий ВПР — просмотр всех задач по номеру.
     */
    public function taskDatabase(Request $request)
    {
        $user  = Auth::user();
        $grade = (int) ($user->grade_num ?? 8);
        $maxTopic = $grade === 8 ? 18 : 17;

        $topicIds = array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), range(1, $maxTopic));

        $selected = str_pad((string) $request->query('topic', '1'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topicIds, true)) {
            $selected = '01';
        }

        $taskData = new VprTaskDataService($grade);
        $blocks   = $taskData->getBlocks($selected);

        $zadaniya = [];
        foreach ($blocks as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text       = trim((string) ($t['text'] ?? ''));
                    $expression = trim((string) ($t['expression'] ?? ''));
                    $question   = trim((string) ($t['question'] ?? ''));
                    if ($text === '' && $expression === '' && empty($t['svg']) && empty($t['image']) && $question === '') {
                        continue;
                    }
                    $tasks[] = [
                        'id'         => $t['id'] ?? null,
                        'text'       => $text,
                        'expression' => $expression !== '' ? $expression : null,
                        'svg'        => $t['svg'] ?? null,
                        'image'      => $t['image'] ?? null,
                        'options'    => $t['options'] ?? null,
                        'question'   => $question !== '' ? $question : null,
                        'answer'     => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $num   = $zadanie['number'] ?? '';
                    $label = trim((string) ($zadanie['label'] ?? ''));
                    $instr = trim((string) ($zadanie['instruction'] ?? ''));
                    $title = $label !== '' ? $label : ($instr !== '' ? "Задание {$num}. {$instr}" : "Задание {$num}");
                    $zadaniya[] = ['title' => $title, 'tasks' => $tasks];
                }
            }
        }

        $taskCount = array_sum(array_map(fn($z) => count($z['tasks']), $zadaniya));

        return view('pwa.student.vpr-tasks', compact(
            'grade', 'topicIds', 'selected', 'zadaniya', 'taskCount', 'maxTopic'
        ));
    }

    /**
     * Страница результатов ВПР.
     */
    public function results(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->where('status', 'submitted')
            ->with(['variant', 'scorings'])
            ->firstOrFail();

        $scoring = OgeAttemptScoring::where('attempt_id', $attempt->id)->first();

        return view('pwa.student.vpr-results', compact('user', 'attempt', 'scoring'));
    }
}
