<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JarvisMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JarvisMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $materials = JarvisMaterial::query()
            ->when($user->role !== 'admin', fn ($q) => $q->where('owner_teacher_id', $user->id))
            ->latest('updated_at')
            ->get();

        return response()->json(['data' => $materials]);
    }

    public function show(Request $request, JarvisMaterial $material): JsonResponse
    {
        $this->authorizeOwner($request, $material);

        return response()->json(['data' => $material]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'source_content' => ['required', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        $material = JarvisMaterial::create([
            'owner_teacher_id' => $request->user()->id,
            'title' => $payload['title'],
            'slug' => JarvisMaterial::generateUniqueSlug($payload['slug'] ?? $payload['title']),
            'excerpt' => $payload['excerpt'] ?? null,
            'category' => $payload['category'] ?? null,
            'source_content' => $payload['source_content'],
            'status' => JarvisMaterial::STATUS_DRAFT,
            'published_at' => null,
            'meta' => $payload['meta'] ?? null,
        ]);

        return response()->json(['data' => $material], 201);
    }

    public function update(Request $request, JarvisMaterial $material): JsonResponse
    {
        $this->authorizeOwner($request, $material);

        $payload = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'source_content' => ['sometimes', 'required', 'string'],
            'meta' => ['nullable', 'array'],
            'status' => ['sometimes', Rule::in([
                JarvisMaterial::STATUS_DRAFT,
                JarvisMaterial::STATUS_PUBLISHED,
                JarvisMaterial::STATUS_ARCHIVED,
            ])],
        ]);

        if (array_key_exists('title', $payload)) {
            $material->title = $payload['title'];
        }

        if (array_key_exists('slug', $payload) && $payload['slug'] !== null && $payload['slug'] !== '') {
            $material->slug = JarvisMaterial::generateUniqueSlug($payload['slug'], $material->id);
        }

        if (array_key_exists('excerpt', $payload)) {
            $material->excerpt = $payload['excerpt'];
        }

        if (array_key_exists('category', $payload)) {
            $material->category = $payload['category'];
        }

        if (array_key_exists('source_content', $payload)) {
            $material->source_content = $payload['source_content'];
        }

        if (array_key_exists('meta', $payload)) {
            $material->meta = $payload['meta'];
        }

        if (array_key_exists('status', $payload)) {
            $material->status = $payload['status'];
            if ($payload['status'] === JarvisMaterial::STATUS_PUBLISHED && !$material->published_at) {
                $material->published_at = now();
            }
            if ($payload['status'] !== JarvisMaterial::STATUS_PUBLISHED) {
                $material->published_at = null;
            }
        }

        $material->save();

        return response()->json(['data' => $material->fresh()]);
    }

    public function publish(Request $request, JarvisMaterial $material): JsonResponse
    {
        $this->authorizeOwner($request, $material);

        $material->slug = JarvisMaterial::generateUniqueSlug($material->title, $material->id);
        $material->status = JarvisMaterial::STATUS_PUBLISHED;
        $material->published_at = now();
        $material->save();

        return response()->json(['data' => $material->fresh()]);
    }

    public function archive(Request $request, JarvisMaterial $material): JsonResponse
    {
        $this->authorizeOwner($request, $material);

        $material->status = JarvisMaterial::STATUS_ARCHIVED;
        $material->published_at = null;
        $material->save();

        return response()->json(['data' => $material->fresh()]);
    }

    protected function authorizeOwner(Request $request, JarvisMaterial $material): void
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return;
        }

        abort_if((int) $material->owner_teacher_id !== (int) $user->id, 403);
    }
}
