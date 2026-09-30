<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Services\FriendInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Страница «Позови друга» в кабинете ученика: у 8–11 класса — деньги,
 * у 6–7 — скидка 50%. Админ смотрит любую через ?program=cash|discount.
 */
class FriendInviteController extends Controller
{
    public function __construct(private readonly FriendInviteService $friends) {}

    public function show(Request $request)
    {
        $user = Auth::user();
        $program = $this->friends->programFor($user);
        abort_if($program === null, 404);
        if ($user->isAdmin() && in_array($request->query('program'), [FriendInviteService::CASH, FriendInviteService::DISCOUNT], true)) {
            $program = $request->query('program');
        }

        return view($program === FriendInviteService::DISCOUNT ? 'pwa.student.friends-discount' : 'pwa.student.friends', [
            'user' => $user,
            'summary' => $this->friends->summary($user),
            'board' => $this->friends->board($program, $user),
            'svc' => $this->friends,
        ]);
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
