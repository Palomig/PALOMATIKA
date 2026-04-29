<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\PracticeGameRun;
use App\Services\PracticeGameService;
use App\Services\PracticeLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PracticeController extends Controller
{
    public function __construct(
        private readonly PracticeGameService $practiceGames,
        private readonly PracticeLeaderboardService $leaderboard,
    ) {}

    public function index()
    {
        return view('pwa.student.practice.index', [
            'categories' => $this->practiceGames->allCategories(),
        ]);
    }

    public function category(string $slug)
    {
        $category = $this->practiceGames->getCategory($slug);

        return view('pwa.student.practice.category', [
            'category' => $category,
            'games' => $this->practiceGames->gamesByCategory($slug),
        ]);
    }

    public function showMiniGame(string $slug)
    {
        return view('pwa.student.practice.game-topic', [
            'game' => $this->practiceGames->getMiniGame($slug),
        ]);
    }

    public function startRun(Request $request, string $slug): JsonResponse
    {
        $game = $this->practiceGames->getMiniGame($slug);
        $result = $this->practiceGames->startRun($request->user(), $slug);

        return response()->json([
            'run_id' => $result['run']->id,
            'game' => [
                'slug' => $game['slug'],
                'title' => $game['title'],
                'theory' => $game['theory'],
            ],
            'score' => 0,
            'question' => $result['question'],
        ]);
    }

    public function answerRun(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer',
            'option_id' => 'required|string|max:8',
        ]);

        $run = $this->resolveRun($request, $slug, (int) $data['run_id']);
        if ($run instanceof JsonResponse) {
            return $run;
        }

        $result = $this->practiceGames->submitAnswer($run, (string) $data['option_id']);

        return response()->json($result);
    }

    public function timeoutRun(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer',
        ]);

        $run = $this->resolveRun($request, $slug, (int) $data['run_id']);
        if ($run instanceof JsonResponse) {
            return $run;
        }

        $result = $this->practiceGames->markTimeout($run);

        return response()->json($result);
    }

    public function leaderboard(Request $request, string $slug)
    {
        $game = $this->practiceGames->getMiniGame($slug);
        $scope = (string) $request->query('scope', 'class');
        $period = (string) $request->query('period', 'all_time');

        if (!in_array($scope, ['class', 'school', 'all'], true)) {
            $scope = 'class';
        }
        if (!in_array($period, ['all_time', 'week'], true)) {
            $period = 'all_time';
        }

        $board = $this->leaderboard->topRuns($slug, $scope, $period, $request->user());

        return view('pwa.student.practice.leaderboard', [
            'game' => $game,
            'scope' => $scope,
            'period' => $period,
            'board' => $board,
            'availableScopes' => $this->leaderboard->availableScopes($request->user()),
        ]);
    }

    private function resolveRun(Request $request, string $slug, int $runId): PracticeGameRun|JsonResponse
    {
        $run = PracticeGameRun::find($runId);

        if (!$run || $run->user_id !== $request->user()->id || $run->slug !== $slug) {
            return response()->json(['error' => 'run_not_found'], Response::HTTP_NOT_FOUND);
        }

        return $run;
    }
}
