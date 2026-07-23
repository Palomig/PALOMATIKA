<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Services\Entrance10Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Раздел «Практика» → Контрольные для поступления в 10 класс.
 *
 *  • index    — два подраздела: полные варианты и отработка по номерам.
 *  • variant  — полный вариант целиком (как ОГЭ, но только эти задания).
 *  • number   — тренажёр одного номера: статические задачи из вариантов + генерация.
 *  • API check/generate — stateless проверка/раскрытие ответа и выдача новой задачи.
 */
class Entrance10Controller extends Controller
{
    public function __construct(private readonly Entrance10Service $service)
    {
    }

    public function index()
    {
        return view('pwa.student.entrance10.index', [
            'meta' => $this->service->meta(),
            'numbers' => $this->service->numbers(),
            'variantNumbers' => $this->service->variantNumbers(),
        ]);
    }

    public function variant(int $variant)
    {
        try {
            $data = $this->service->variantForView($variant);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return view('pwa.student.entrance10.variant', [
            'meta' => $this->service->meta(),
            'variant' => $data,
        ]);
    }

    public function number(int $number)
    {
        try {
            $info = $this->service->numberInfo($number);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return view('pwa.student.entrance10.number', [
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
            $task = $this->service->generatedTaskForView($number);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'unknown_number'], 404);
        }

        return response()->json(['task' => $task]);
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'answer' => 'nullable|string|max:200',
            'reveal' => 'nullable|boolean',
        ]);

        $result = $this->service->check(
            $data['token'],
            (string) ($data['answer'] ?? ''),
            (bool) ($data['reveal'] ?? false),
        );

        if (($result['status'] ?? '') === 'bad_token') {
            return response()->json(['error' => 'bad_token'], 422);
        }

        return response()->json($result);
    }
}
