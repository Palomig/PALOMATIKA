<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\FriendInviteNote;
use App\Services\FriendInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Страница «Позови друга» в кабинете ученика. */
class FriendInviteController extends Controller
{
    public function __construct(private readonly FriendInviteService $friends) {}

    public function show()
    {
        $user = Auth::user();
        abort_unless($this->friends->isEligible($user), 404);

        return view('pwa.student.friends', [
            'user' => $user,
            'summary' => $this->friends->summary($user),
            'board' => $this->friends->board($user),
            'svc' => $this->friends,
        ]);
    }

    public function storeNote(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->friends->isEligible($user), 404);
        $data = $request->validate(['name' => 'required|string|max:100']);

        if (FriendInviteNote::where('user_id', $user->id)->count() >= FriendInviteService::MAX_OPEN_NOTES) {
            return back()->with('friends_error', 'Отметок уже ' . FriendInviteService::MAX_OPEN_NOTES . ' — удали старую, чтобы добавить новую');
        }
        FriendInviteNote::create(['user_id' => $user->id, 'name' => trim($data['name'])]);

        return back();
    }

    public function destroyNote(int $id)
    {
        FriendInviteNote::where('user_id', Auth::id())->whereKey($id)->delete();

        return back();
    }

    public function boardVisibility(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->friends->isEligible($user), 404);
        $user->invite_board_hidden = ! $request->boolean('show');
        $user->save();

        return back();
    }
}
