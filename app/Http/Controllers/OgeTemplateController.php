<?php

namespace App\Http\Controllers;

use App\Models\OgeGeneratorTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OgeTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = OgeGeneratorTemplate::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get()
            ->map(fn (OgeGeneratorTemplate $template) => $this->serializeTemplate($template))
            ->values();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'zadaniya' => 'required|array|min:1',
            'zadaniya.*' => 'string|max:64',
        ]);

        $zadaniya = array_values(array_unique(array_map('strval', $validated['zadaniya'])));

        $template = OgeGeneratorTemplate::query()->create([
            'user_id' => $request->user()->id,
            'name' => trim($validated['name']),
            'zadaniya_json' => $zadaniya,
        ]);

        return response()->json([
            'success' => true,
            'template' => $this->serializeTemplate($template),
        ], 201);
    }

    public function destroy(Request $request, int $templateId): JsonResponse
    {
        $template = OgeGeneratorTemplate::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $templateId)
            ->firstOrFail();

        $template->delete();

        return response()->json(['success' => true]);
    }

    private function serializeTemplate(OgeGeneratorTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'zadaniya' => $template->zadaniya_json ?? [],
            'created_at' => optional($template->created_at)->toIso8601String(),
            'updated_at' => optional($template->updated_at)->toIso8601String(),
        ];
    }
}
