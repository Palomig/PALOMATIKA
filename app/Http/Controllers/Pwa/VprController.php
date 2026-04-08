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
