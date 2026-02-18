<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OgeReviewController extends Controller
{
    public function teachers(): View
    {
        $teachers = User::query()
            ->whereIn('role', ['teacher', 'admin'])
            ->withCount('ownedOgeVariants')
            ->orderBy('name')
            ->get();

        return view('teacher.oge.teachers', compact('teachers'));
    }

    public function variants(int $teacherId): View
    {
        $teacher = User::findOrFail($teacherId);

        $variants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount('attempts')
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.oge.variants', compact('teacher', 'variants'));
    }

    public function results(int $variantId): View
    {
        $variant = OgeVariant::query()
            ->with('ownerTeacher')
            ->findOrFail($variantId);

        $attempts = $variant->attempts()
            ->with(['student:id,name,email', 'answers', 'taskTimings', 'scorings'])
            ->orderByDesc('created_at')
            ->get();

        $customTasks = $this->loadCustomTasksByHash($variant->hash);
        $customColumns = [];

        if (!empty($customTasks)) {
            foreach (array_values($customTasks) as $index => $task) {
                $taskNumber = 6 + $index;
                $topicId = str_pad((string) ($task['topic_id'] ?? ''), 2, '0', STR_PAD_LEFT);

                $customColumns[] = [
                    'task_number' => $taskNumber,
                    'topic_id' => $topicId,
                    'label' => $topicId !== '00'
                        ? sprintf('%s.%d', $topicId, $index + 1)
                        : (string) $taskNumber,
                ];
            }
        }

        return view('teacher.oge.results', [
            'variant' => $variant,
            'attempts' => $attempts,
            'customColumns' => $customColumns,
            'isCustomVariant' => !empty($customColumns),
        ]);
    }

    /**
     * @return array<int, array>
     */
    private function loadCustomTasksByHash(string $hash): array
    {
        if (!preg_match('/^[a-z0-9]{8}$/', $hash)) {
            return [];
        }

        $path = "custom_random_tests/{$hash}.json";

        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        $payload = json_decode((string) Storage::disk('local')->get($path), true);
        return is_array($payload) ? $payload : [];
    }
}

