<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\FriendBoardEntry;
use App\Models\FriendInvite;
use App\Models\FriendInviteBonus;
use App\Models\FriendInviteCredit;
use App\Services\FriendInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * «Позови друга» у преподавателя: на первом занятии спросить новичка, кто его
 * привёл, и записать сразу; когда он оплатит месяц — отметить; выдать деньги
 * (8–11) или применить скидки (6–7). Супер-админ ведёт доски зовущих вручную.
 */
class TeacherFriendInviteController extends Controller
{
    public function __construct(private readonly FriendInviteService $friends) {}

    public function index()
    {
        $teacher = Auth::user();
        $superAdmin = $teacher->isSuperAdmin();

        return view('pwa.teacher.friends', [
            'referrers' => $this->friends->eligibleReferrers($teacher->id),
            'superAdmin' => $superAdmin,
            'boards' => $superAdmin ? [
                FriendInviteService::CASH => FriendBoardEntry::where('program', FriendInviteService::CASH)
                    ->with('user:id,name,grade_num')->orderByDesc('friends')->orderBy('id')->get(),
                FriendInviteService::DISCOUNT => FriendBoardEntry::where('program', FriendInviteService::DISCOUNT)
                    ->with('user:id,name,grade_num')->orderByDesc('friends')->orderBy('id')->get(),
            ] : [],
            'pending' => FriendInvite::whereNull('qualified_at')->whereNull('cancelled_at')
                ->with('credits.referrer:id,name')->orderByDesc('first_lesson_on')->get(),
            'queue' => $this->friends->payoutQueue(),
            'paidCredits' => FriendInviteCredit::whereNotNull('paid_at')
                ->whereHas('invite', fn ($q) => $q->where('program', FriendInviteService::CASH))
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

        return back()->with('friends_ok', $invite->invitee_name . ($invite->program === FriendInviteService::DISCOUNT
            ? ': скидки встали в очередь' : ': выплата начислена'));
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

    /** Скидка 50% новичку (акция 6–7) применена к его следующему месяцу. */
    public function applyInviteeDiscount(FriendInvite $invite)
    {
        if ($invite->program === FriendInviteService::DISCOUNT && $invite->qualified_at !== null && $invite->invitee_discount_applied_at === null) {
            $invite->update(['invitee_discount_applied_at' => now()]);
        }

        return back();
    }

    /** Доска зовущих ведётся вручную: добавить ученика или поменять число друзей. */
    public function boardStore(Request $request)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'program' => 'required|in:' . FriendInviteService::CASH . ',' . FriendInviteService::DISCOUNT,
            'user_id' => 'required|integer',
            'friends' => 'required|integer|between:0,99',
        ]);
        $allowed = $this->friends->eligibleReferrers(null, $data['program'])->pluck('id')->all();
        if (! in_array((int) $data['user_id'], $allowed, true)) {
            return back()->with('friends_error', 'Этот ученик не участвует в этой акции');
        }
        FriendBoardEntry::updateOrCreate(
            ['program' => $data['program'], 'user_id' => (int) $data['user_id']],
            ['friends' => (int) $data['friends']]
        );

        return back()->with('friends_ok', 'Доска обновлена');
    }

    public function boardDestroy(FriendBoardEntry $entry)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        $entry->delete();

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
