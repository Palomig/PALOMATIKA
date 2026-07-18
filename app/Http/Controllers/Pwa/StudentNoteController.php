<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\StudentNote;
use Illuminate\Http\Request;

class StudentNoteController extends Controller
{
    public function update(Request $request, int $id)
    {
        $note = StudentNote::findOrFail($id);
        if ($note->teacher_id !== $request->user()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'body'      => 'sometimes|string|max:2000',
            'kind'      => 'sometimes|in:weakness,strength,todo,general',
            'topic_tag' => 'nullable|string|max:120',
        ]);

        $note->update($validated);

        return response()->json(['note' => $note]);
    }

    public function destroy(Request $request, int $id)
    {
        $note = StudentNote::findOrFail($id);
        if ($note->teacher_id !== $request->user()->id) {
            abort(404);
        }

        $note->delete();

        return response()->json(['ok' => true]);
    }
}
