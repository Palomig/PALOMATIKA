<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\OgeVariant;
use App\Models\User;
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

        $taskNumbers = $this->resolveTaskNumbers($variant, $attempts);

        return view('teacher.oge.results', compact('variant', 'attempts', 'taskNumbers'));
    }

    private function resolveTaskNumbers(OgeVariant $variant, $attempts): array
    {
        if (!$variant->isCustomRandom()) {
            return range(6, 19);
        }

        $configNumbers = $variant->config_json['custom_task_numbers'] ?? [];
        $fromConfig = is_array($configNumbers)
            ? array_values(array_filter(array_map('intval', $configNumbers), fn (int $number): bool => $number > 0 && $number <= 255))
            : [];

        $fromAttempts = $attempts
            ->flatMap(fn ($attempt) => $attempt->answers->pluck('task_number'))
            ->map(fn ($number) => (int) $number)
            ->filter(fn (int $number): bool => $number > 0 && $number <= 255)
            ->values()
            ->all();

        $resolved = array_values(array_unique(array_merge($fromConfig, $fromAttempts)));
        sort($resolved);

        return !empty($resolved) ? $resolved : range(1, 19);
    }
}
