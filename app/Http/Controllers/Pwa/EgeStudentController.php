<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use App\Services\EgeVariantPoolService;
use App\Services\OgeAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EgeStudentController extends Controller
{
    private function makePool(): EgeVariantPoolService
    {
        $taskData = app(EgeTaskDataService::class);
        $builder  = new EgeVariantBuilderService($taskData);
        return new EgeVariantPoolService($taskData, $builder);
    }

    public function home(Request $request)
    {
        $user  = Auth::user();
        $grade = $user->grade_num;

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

        return view('pwa.student.ege-home', compact('user', 'grade', 'activeList'));
    }

    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $user = Auth::user();
        if ($user->grade_num < 10 || $user->grade_num > 11) {
            abort(403, 'ЕГЭ доступно только для 10–11 классов');
        }

        $variant = $this->makePool()->getOrCreateVariant($user);
        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        return redirect()->route('pwa.student.ege.test', $attempt->id);
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
        $tasks   = $variant->config_json['tasks'] ?? [];
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
