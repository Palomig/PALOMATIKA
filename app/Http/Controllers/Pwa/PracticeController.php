<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Services\PracticeGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    public function __construct(
        private readonly PracticeGameService $practiceGames,
    ) {}

    public function index()
    {
        return view('pwa.student.practice.index');
    }

    public function miniGames()
    {
        return view('pwa.student.practice.mini-games', [
            'games' => $this->practiceGames->allMiniGames(),
        ]);
    }

    public function showMiniGame(string $slug)
    {
        return view('pwa.student.practice.game-topic', [
            'game' => $this->practiceGames->getMiniGame($slug),
        ]);
    }

    public function question(Request $request, string $slug): JsonResponse
    {
        $score = max(0, (int) $request->query('score', 0));
        $game = $this->practiceGames->getMiniGame($slug);
        $question = $this->practiceGames->generateQuestion($slug, $score);

        return response()->json([
            'game' => [
                'slug' => $game['slug'],
                'title' => $game['title'],
                'theory' => $game['theory'],
            ],
            'question' => $question,
        ]);
    }
}
