<?php

namespace App\Http\Controllers;

use App\Models\JarvisMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class JarvisMaterialPageController extends Controller
{
    public function index(): View
    {
        $materials = JarvisMaterial::query()
            ->where('status', JarvisMaterial::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $categories = $materials
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('materials.index', [
            'materials' => $materials,
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): View
    {
        $material = JarvisMaterial::query()
            ->where('slug', $slug)
            ->where('status', JarvisMaterial::STATUS_PUBLISHED)
            ->firstOrFail();

        return view('materials.show', [
            'material' => $material,
        ]);
    }

    public function teacherIndex(Request $request): View
    {
        $user = $request->user();

        $materials = JarvisMaterial::query()
            ->when($user->role !== 'admin', fn ($q) => $q->where('owner_teacher_id', $user->id))
            ->latest('updated_at')
            ->get();

        return view('teacher.materials.index', [
            'materials' => $materials,
        ]);
    }
}
