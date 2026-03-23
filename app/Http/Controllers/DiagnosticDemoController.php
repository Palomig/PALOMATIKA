<?php

namespace App\Http\Controllers;

use App\Data\DiagnosticQuestionBank;
use App\Models\Skill;
use Illuminate\Http\Request;

class DiagnosticDemoController extends Controller
{
    public function show()
    {
        $questions = $this->buildQuestions();

        return view('demo.diagnostic', [
            'questions'      => $questions,
            'totalQuestions' => count($questions),
        ]);
    }

    public function check(Request $request)
    {
        $questions = $this->buildQuestions();
        $answers   = $request->input('answers', []);

        $results = [];
        foreach ($questions as $i => $q) {
            $userChoice    = (string) ($answers[$i] ?? '');
            $correctChoice = (string) $q['correct_answer'];
            $results[] = [
                'skill_name'   => $q['skill_name'],
                'category'     => $q['category'],
                'question'     => $q['question']['question'],
                'choices'      => $q['question']['choices'],
                'user_choice'  => $userChoice,
                'correct'      => $correctChoice,
                'is_correct'   => $userChoice === $correctChoice,
            ];
        }

        $score   = count(array_filter($results, fn ($r) => $r['is_correct']));
        $total   = count($results);
        $percent = $total > 0 ? round($score / $total * 100) : 0;

        return view('demo.diagnostic-results', compact('results', 'score', 'total', 'percent'));
    }

    public function review()
    {
        $bank     = DiagnosticQuestionBank::all();
        $skillIds = array_keys($bank);
        $skills   = Skill::whereIn('id', $skillIds)->where('is_active', true)->get()->keyBy('id');

        $reviewPath = storage_path('app/diagnostic_review.json');
        $saved      = file_exists($reviewPath)
            ? (json_decode(file_get_contents($reviewPath), true) ?? [])
            : [];
        $approved = $saved['approved'] ?? null;
        $rejected = $saved['rejected'] ?? [];

        $items = [];
        foreach ($bank as $skillId => $mc) {
            $skill = $skills->get($skillId);
            $items[] = [
                'skill_id'   => $skillId,
                'skill_name' => $skill?->name ?? "Навык #$skillId",
                'category'   => $skill?->category ?? '',
                'question'   => $mc['question'],
                'choices'    => $mc['choices'],
                'correct'    => $mc['correct'],
                'status'     => $approved === null
                    ? 'pending'
                    : (in_array($skillId, $approved, true)
                        ? 'approved'
                        : (in_array($skillId, $rejected, true) ? 'rejected' : 'pending')),
            ];
        }

        return view('demo.diagnostic-review', [
            'items'         => $items,
            'approvedCount' => $approved !== null ? count($approved) : 0,
            'rejectedCount' => count($rejected),
            'totalCount'    => count($items),
        ]);
    }

    public function saveReview(Request $request)
    {
        $approved = array_map('intval', $request->input('approved', []));
        $rejected = array_map('intval', $request->input('rejected', []));

        $data = ['approved' => $approved, 'rejected' => $rejected];
        file_put_contents(storage_path('app/diagnostic_review.json'), json_encode($data, JSON_PRETTY_PRINT));

        return redirect()->route('demo.diagnostic.review')->with('saved', true);
    }

    private function buildQuestions(): array
    {
        $bank     = DiagnosticQuestionBank::all();
        $skillIds = array_keys($bank);
        $skills   = Skill::whereIn('id', $skillIds)->where('is_active', true)->get()->keyBy('id');

        $questions = [];
        foreach ($bank as $skillId => $mc) {
            $skill = $skills->get($skillId);
            if (!$skill) continue;

            $questions[] = [
                'skill_id'       => $skillId,
                'skill_name'     => $skill->name,
                'category'       => $skill->category ?? '',
                'type'           => 'mc',
                'question'       => ['question' => $mc['question'], 'choices' => $mc['choices']],
                'correct_answer' => (string) $mc['correct'],
            ];
        }

        return $questions;
    }
}
