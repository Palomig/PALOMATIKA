<?php

namespace App\Http\Controllers;

use App\Data\DiagnosticQuestionBank;
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
        $bankIds  = array_keys($bank);

        $saved    = DiagnosticQuestionBank::loadReview();
        $approved = $saved['approved'] ?? null;
        $rejected = $saved['rejected'] ?? [];

        // Если сохранённые ID не совпадают с текущим банком — считаем данные устаревшими
        $savedIds   = array_merge($approved ?? [], $rejected);
        $isStale    = !empty($savedIds) && !empty(array_diff($savedIds, $bankIds));
        if ($isStale) {
            $approved = null;
            $rejected = [];
        }

        $items = [];
        $prevTopic = null;
        foreach ($bank as $id => $q) {
            $items[] = [
                'id'         => $id,
                'topic_id'   => $q['topic_id'],
                'topic_name' => $q['topic_name'],
                'is_new_topic' => $q['topic_id'] !== $prevTopic,
                'question'   => $q['question'],
                'choices'    => $q['choices'],
                'correct'    => $q['correct'],
                'status'     => $approved === null
                    ? 'pending'
                    : (in_array($id, $approved, true)
                        ? 'approved'
                        : (in_array($id, $rejected, true) ? 'rejected' : 'pending')),
            ];
            $prevTopic = $q['topic_id'];
        }

        return view('demo.diagnostic-review', [
            'items'         => $items,
            'approvedCount' => $approved !== null ? count($approved) : 0,
            'rejectedCount' => count($rejected),
            'totalCount'    => count($items),
            'isStale'       => $isStale,
        ]);
    }

    public function saveReview(Request $request)
    {
        if ($request->input('reset')) {
            DiagnosticQuestionBank::saveReview([], []);
            return redirect()->route('demo.diagnostic.review');
        }

        $approved = array_map('intval', $request->input('approved', []));
        $rejected = array_map('intval', $request->input('rejected', []));

        DiagnosticQuestionBank::saveReview($approved, $rejected);

        return redirect()->route('demo.diagnostic.review')->with('saved', true);
    }

    private function buildQuestions(): array
    {
        $questions = [];
        foreach (DiagnosticQuestionBank::all() as $id => $q) {
            $questions[] = [
                'skill_id'       => $id,
                'skill_name'     => $q['topic_name'],
                'category'       => 'Задание ' . $q['topic_id'],
                'type'           => 'mc',
                'question'       => ['question' => $q['question'], 'choices' => $q['choices']],
                'correct_answer' => (string) $q['correct'],
            ];
        }
        return $questions;
    }
}
