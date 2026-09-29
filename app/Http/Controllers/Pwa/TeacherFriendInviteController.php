<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\FriendInvite;
use App\Models\FriendInviteBonus;
use App\Models\FriendInviteCredit;
use App\Models\FriendInviteNote;
use App\Services\FriendInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * «Позови друга» у преподавателя: на первом занятии спросить новичка, кто его
 * привёл, и записать сразу; потом отметить второе оплаченное занятие и выдачу денег.
 */
class TeacherFriendInviteController extends Controller
{
    public function __construct(private readonly FriendInviteService $friends) {}

    public function index()
    {
        $teacher = Auth::user();
        $referrers = $this->friends->eligibleReferrers($teacher->id);
        $notes = FriendInviteNote::whereIn('user_id', $referrers->pluck('id'))
            ->latest('id')->get()->groupBy('user_id');

        return view('pwa.teacher.friends', [
            'referrers' => $referrers,
            'notes' => $notes,
            'pending' => FriendInvite::whereNull('qualified_at')->whereNull('cancelled_at')
                ->with('credits.referrer:id,name')->orderByDesc('first_lesson_on')->get(),
            'queue' => $this->friends->payoutQueue(),
            'paidCredits' => FriendInviteCredit::whereNotNull('paid_at')
                ->with(['referrer:id,name', 'invite:id,invitee_name'])->latest('paid_at')->limit(30)->get(),
            'svc' => $this->friends,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invitee_name' => 'required|string|max:100',
            'invitee_grade' => 'nullable|integer|between:1,11',
            'first_lesson_on' => 'required|date',
            'referrers' => 'required|array|min:1|max:' . FriendInviteService::MAX_REFERRERS,
            'referrers.*' => 'integer',
        ], [
            'referrers.required' => 'Отметь, кто привёл',
            'referrers.max' => 'Не больше трёх человек',
        ]);

        try {
            $this->friends->register(
                Auth::user(),
                $data['invitee_name'],
                isset($data['invitee_grade']) ? (int) $data['invitee_grade'] : null,
                Carbon::parse($data['first_lesson_on']),
                $data['referrers'],
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('friends_error', $e->getMessage());
        }

        return back()->with('friends_ok', 'Записано: ' . $data['invitee_name']);
    }

    public function qualify(FriendInvite $invite)
    {
        $this->friends->qualify($invite, Auth::user());

        return back()->with('friends_ok', $invite->invitee_name . ': выплата начислена');
    }

    public function cancel(FriendInvite $invite)
    {
        $this->friends->cancel($invite);

        return back();
    }

    public function payCredit(FriendInviteCredit $credit)
    {
        if ($credit->paid_at === null && $credit->invite?->qualified_at !== null) {
            $credit->update(['paid_at' => now(), 'paid_by' => Auth::id()]);
        }

        return back();
    }

    public function payBonus(FriendInviteBonus $bonus)
    {
        if ($bonus->paid_at === null) {
            $bonus->update(['paid_at' => now(), 'paid_by' => Auth::id()]);
        }

        return back();
    }
}
