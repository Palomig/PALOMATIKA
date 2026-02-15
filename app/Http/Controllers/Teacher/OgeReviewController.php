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

        return view('teacher.oge.results', compact('variant', 'attempts'));
    }
}

