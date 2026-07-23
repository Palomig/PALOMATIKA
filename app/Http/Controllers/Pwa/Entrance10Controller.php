<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Services\Entrance10Service;
use App\Services\StudentExamAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Практика → Контрольные для поступления в 10 класс.
 *
 * Реализовано как полный вариант ОГЭ: собранный вариант (по одному аналогу на номер)
 * проходится по одному заданию на странице (подпункты а/б/в вместе), в конце —
 * результаты с ошибками. Попытки хранятся в таблицах ОГЭ (OgeAttempt/OgeVariant),
 * поэтому история видна и ученику, и учителю там же, где варианты ОГЭ.
 */
class Entrance10Controller extends Controller
{
    public function __construct(
        private readonly Entrance10Service $service,
        private readonly StudentExamAccessService $examAccess,
    ) {}

    // ------------------------------------------------------------------ навигация

    public function index()
    {
        return view('pwa.student.entrance10.index', [
            'meta' => $this->service->meta(),
            'numbers' => $this->service->numbers(),
            'variantNumbers' => $this->service->variantNumbers(),
        ]);
    }

    /** База заданий по одному номеру: оригиналы + «сгенерировать ещё аналог». */
    public function bank(int $number)
    {
        try {
            $info = $this->service->numberInfo($number);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return view('pwa.student.entrance10.bank', [
            'meta' => $this->service->meta(),
            'info' => $info,
            'numbers' => $this->service->numbers(),
            'staticTasks' => $this->service->staticTasksForNumber($number),
            'generatable' => $this->service->isGeneratable($number),
        ]);
    }

    public function generate(int $number): JsonResponse
    {
        if (!$this->service->isGeneratable($number)) {
            return response()->json(['error' => 'not_generatable'], 422);
        }
        try {
            return response()->json(['task' => $this->service->generatedTaskForView($number)]);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'unknown_number'], 404);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'answer' => 'nullable|string|max:200',
            'reveal' => 'nullable|boolean',
        ]);
        $result = $this->service->check($data['token'], (string) ($data['answer'] ?? ''), (bool) ($data['reveal'] ?? false));
        if (($result['status'] ?? '') === 'bad_token') {
            return response()->json(['error' => 'bad_token'], 422);
        }
        return response()->json($result);
    }

    // ------------------------------------------------------------------ полный вариант

    public function start(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Сессия истекла. Перезайдите в приложение.'], 401);
        }
        abort_unless($this->examAccess->canAccessExamType($user, OgeVariant::EXAM_ENTRANCE10), 403);

        $variantNumber = (int) $request->input('variant', 1);
        if (!in_array($variantNumber, $this->service->variantNumbers(), true)) {
            $variantNumber = $this->service->variantNumbers()[0] ?? 1;
        }

        $attempt = $this->service->startVariantAttempt($user, $variantNumber);

        return response()->json([
            'redirect' => route('pwa.student.practice.entrance10.exam', $attempt->id),
        ]);
    }

    public function exam(int $attemptId)
    {
        $attempt = $this->loadOwnAttempt($attemptId);

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect(route('pwa.student.practice.entrance10.results', $attempt->id));
        }

        $variant = $attempt->variant;
        $existing = $attempt->answers()->pluck('current_answer', 'task_number')->toArray();

        return view('pwa.student.entrance10.exam', [
            'attempt' => $attempt,
            'meta' => $variant->config_json['meta'] ?? $this->service->meta(),
            'title' => $variant->title,
            'groups' => $this->service->examGroupsForView($variant),
            'answers' => (object) $existing,
        ]);
    }

    public function answer(Request $request, int $attemptId): JsonResponse
    {
        $attempt = $this->loadOwnAttempt($attemptId);
        if ($attempt->status !== 'active') {
            return response()->json(['status' => 'closed'], 409);
        }
        $data = $request->validate([
            'slot' => 'required|integer|min:1|max:255',
            'answer' => 'nullable|string|max:200',
        ]);
        $this->service->saveAnswer($attempt, (int) $data['slot'], (string) ($data['answer'] ?? ''));
        return response()->json(['status' => 'ok']);
    }

    public function submit(Request $request, int $attemptId): JsonResponse
    {
        $attempt = $this->loadOwnAttempt($attemptId);
        if (in_array($attempt->status, ['submitted', 'scored'], true)) {
            return response()->json(['redirect' => route('pwa.student.practice.entrance10.results', $attempt->id)]);
        }
        $answers = $request->validate(['answers' => 'nullable|array'])['answers'] ?? [];
        $this->service->submitVariantAttempt($attempt, $answers);
        return response()->json(['redirect' => route('pwa.student.practice.entrance10.results', $attempt->id)]);
    }

    public function results(int $attemptId)
    {
        $attempt = $this->loadOwnAttempt($attemptId);
        if (!in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect(route('pwa.student.practice.entrance10.exam', $attempt->id));
        }

        $result = $this->service->resultGroupsForView($attempt);
        $time = ($attempt->started_at && $attempt->submitted_at)
            ? $attempt->submitted_at->diffInSeconds($attempt->started_at) : null;

        return view('pwa.student.entrance10.results', [
            'attempt' => $attempt,
            'title' => $attempt->variant->title,
            'groups' => $result['groups'],
            'earned' => $result['earned'],
            'max' => $result['max'],
            'time' => $time,
        ]);
    }

    private function loadOwnAttempt(int $attemptId): OgeAttempt
    {
        $user = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->with('variant')
            ->firstOrFail();
        abort_unless($attempt->variant?->exam_type === OgeVariant::EXAM_ENTRANCE10, 404);
        return $attempt;
    }
}
