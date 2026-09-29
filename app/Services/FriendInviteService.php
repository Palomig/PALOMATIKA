<?php

namespace App\Services;

use App\Models\FriendInvite;
use App\Models\FriendInviteBonus;
use App\Models\FriendInviteCredit;
use App\Models\FriendInviteNote;
use App\Models\TeacherStudent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * «Позови друга»: 2000 ₽ наличными за каждого друга, который оплатил занятия
 * и пришёл на второе; +3000 ₽ за каждые 6000 ₽ заработанного (каждый третий).
 *
 * Общий друг делит 2000 ₽ поровну, поэтому порог бонуса считается в рублях,
 * а не в друзьях: сговор «записываем друг друга на всех» до бонуса не доводит.
 */
class FriendInviteService
{
    public const REWARD = 2000;
    public const BONUS = 3000;
    public const BONUS_STEP = 6000;
    public const MAX_REFERRERS = 3;
    public const MAX_OPEN_NOTES = 5;
    public const GRADES = [8, 9, 10, 11];

    /**
     * Акция видна только прикреплённым к учителю ученикам 8–11 классов
     * (и админу — превью). Паломатикой пользуются посторонние через открытые
     * банки: показать им 2000 ₽ — значит раздавать деньги интернету.
     */
    public function isEligible(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->role === 'student'
            && in_array((int) $user->grade_num, self::GRADES, true)
            && TeacherStudent::where('student_id', $user->id)->exists();
    }

    /** Ученики, которых преподаватель может отметить как пригласивших. */
    public function eligibleReferrers(?int $teacherId = null): Collection
    {
        $links = TeacherStudent::query()
            ->whereHas('student', fn ($q) => $q->where('role', 'student')->whereIn('grade_num', self::GRADES))
            ->with('student:id,name,grade_num')
            ->get(['teacher_id', 'student_id']);

        $mine = $links->where('teacher_id', $teacherId)->pluck('student_id')->all();

        return $links->pluck('student')->filter()->unique('id')
            ->sortBy(fn (User $s) => [in_array($s->id, $mine, true) ? 0 : 1, mb_strtolower($s->name)])
            ->values();
    }

    /** Доли 2000 ₽ между пригласившими: остаток от деления — первому. */
    public function shares(int $count): array
    {
        if ($count < 1 || $count > self::MAX_REFERRERS) {
            throw new InvalidArgumentException('Пригласивших должно быть от 1 до ' . self::MAX_REFERRERS);
        }
        $base = intdiv(self::REWARD, $count);
        $shares = array_fill(0, $count, $base);
        $shares[0] += self::REWARD - $base * $count;

        return $shares;
    }

    /**
     * Преподаватель на первом занятии записывает новичка и тех, кого он назвал.
     *
     * @param  int[]  $referrerIds
     */
    public function register(User $teacher, string $name, ?int $grade, CarbonInterface $firstLessonOn, array $referrerIds): FriendInvite
    {
        $referrerIds = array_values(array_unique(array_map('intval', $referrerIds)));
        $allowed = $this->eligibleReferrers()->pluck('id')->all();
        foreach ($referrerIds as $id) {
            if (! in_array($id, $allowed, true)) {
                throw new InvalidArgumentException('Пригласивший не участвует в акции');
            }
        }
        $shares = $this->shares(count($referrerIds));

        return DB::transaction(function () use ($teacher, $name, $grade, $firstLessonOn, $referrerIds, $shares) {
            $invite = FriendInvite::create([
                'invitee_name' => trim($name),
                'invitee_grade' => $grade,
                'first_lesson_on' => $firstLessonOn->toDateString(),
                'registered_by' => $teacher->id,
            ]);
            foreach ($referrerIds as $i => $referrerId) {
                $invite->credits()->create(['referrer_id' => $referrerId, 'amount' => $shares[$i]]);
            }

            return $invite;
        });
    }

    /** Друг оплатил занятия и пришёл на второе — доли начислены, бонусы досчитаны. */
    public function qualify(FriendInvite $invite, User $teacher): void
    {
        if (! $invite->isPending()) {
            return;
        }
        DB::transaction(function () use ($invite, $teacher) {
            $invite->update(['qualified_at' => now(), 'qualified_by' => $teacher->id]);
            foreach ($invite->credits()->pluck('referrer_id') as $referrerId) {
                $this->syncBonuses((int) $referrerId);
            }
        });
    }

    public function cancel(FriendInvite $invite): void
    {
        if ($invite->isPending()) {
            $invite->update(['cancelled_at' => now()]);
        }
    }

    /** Заработано: доли по друзьям, дошедшим до второго оплаченного занятия. */
    public function earned(int $referrerId): int
    {
        return (int) FriendInviteCredit::where('referrer_id', $referrerId)
            ->whereHas('invite', fn ($q) => $q->whereNotNull('qualified_at')->whereNull('cancelled_at'))
            ->sum('amount');
    }

    /** Создаёт недостающие бонусы за каждые 6000 ₽. Уже созданные не трогает. */
    public function syncBonuses(int $referrerId): void
    {
        $due = intdiv($this->earned($referrerId), self::BONUS_STEP);
        for ($k = 1; $k <= $due; $k++) {
            FriendInviteBonus::firstOrCreate(
                ['referrer_id' => $referrerId, 'threshold' => $k * self::BONUS_STEP],
                ['amount' => self::BONUS]
            );
        }
    }

    /** Всё, что нужно странице ученика и полоске под УРОК. */
    public function summary(User $user): array
    {
        $credits = FriendInviteCredit::where('referrer_id', $user->id)
            ->with(['invite.credits.referrer:id,name'])
            ->get()
            ->filter(fn (FriendInviteCredit $c) => $c->invite && $c->invite->cancelled_at === null)
            ->sortByDesc(fn (FriendInviteCredit $c) => $c->invite->first_lesson_on)
            ->values();

        $friends = $credits->map(function (FriendInviteCredit $c) use ($user) {
            $others = $c->invite->credits
                ->where('referrer_id', '!=', $user->id)
                ->map(fn ($o) => $this->shortName($o->referrer?->name))
                ->filter()->values()->all();

            return [
                'name' => $this->shortName($c->invite->invitee_name),
                'amount' => $c->amount,
                'others' => $others,
                'first_lesson_on' => $c->invite->first_lesson_on,
                'qualified' => $c->invite->qualified_at !== null,
                'paid_at' => $c->paid_at,
            ];
        });

        $bonuses = FriendInviteBonus::where('referrer_id', $user->id)->orderBy('threshold')->get();
        $earned = $this->earned($user->id);
        $bonusTotal = (int) $bonuses->sum('amount');

        return [
            'earned' => $earned,
            'bonusTotal' => $bonusTotal,
            'total' => $earned + $bonusTotal,
            'toBonus' => self::BONUS_STEP - ($earned % self::BONUS_STEP),
            'progressPct' => (int) round(($earned % self::BONUS_STEP) / self::BONUS_STEP * 100),
            'friends' => $friends,
            'pending' => $friends->where('qualified', false)->values(),
            'bonuses' => $bonuses,
            'notes' => FriendInviteNote::where('user_id', $user->id)->latest('id')->get(),
        ];
    }

    /** @var array<int, array> полоска считается и для дашборда, и для самой строки */
    private array $stripCache = [];

    /** Текст полоски под кнопкой УРОК: новость важнее суммы, сумма важнее оффера. */
    public function strip(User $user): array
    {
        return $this->stripCache[$user->id] ??= $this->buildStrip($user);
    }

    private function buildStrip(User $user): array
    {
        $s = $this->summary($user);
        $pending = $s['pending']->first();
        if ($pending) {
            return [
                'tone' => 'warm',
                'icon' => '🔥',
                'title' => $pending['name'] . ' пришёл на первое занятие',
                'sub' => 'Оплатит и придёт на второе — ' . $this->rub($pending['amount']) . ' твои',
            ];
        }
        if ($s['earned'] > 0) {
            return [
                'tone' => 'gold',
                'icon' => '🏆',
                'title' => 'Заработано ' . $this->rub($s['total']),
                'sub' => 'Ещё ' . $this->rub($s['toBonus']) . ' — и бонус +' . $this->rub(self::BONUS),
            ];
        }

        return [
            'tone' => '',
            'icon' => '🎁',
            'title' => 'Позови друга — ' . $this->rub(self::REWARD),
            'sub' => 'Живыми деньгами, за каждого',
        ];
    }

    /**
     * Доска зовущих за учебный год: сколько друзей дошли до первого занятия.
     * Денег на доске нет, поэтому общий друг засчитывается каждому целиком.
     */
    public function board(User $viewer, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $yearStart = $now->copy()->month(9)->day(1)->startOfDay();
        if ($now->month < 9) {
            $yearStart->subYear();
        }

        $rows = FriendInviteCredit::query()
            ->join('friend_invites', 'friend_invites.id', '=', 'friend_invite_credits.invite_id')
            ->whereNull('friend_invites.cancelled_at')
            ->where('friend_invites.first_lesson_on', '>=', $yearStart->toDateString())
            ->groupBy('friend_invite_credits.referrer_id')
            ->selectRaw('friend_invite_credits.referrer_id, COUNT(DISTINCT friend_invite_credits.invite_id) AS friends')
            ->orderByDesc('friends')
            ->get();

        $users = User::whereIn('id', $rows->pluck('referrer_id'))->get(['id', 'name', 'grade_num', 'invite_board_hidden'])->keyBy('id');

        $list = $rows->values()->map(fn ($r, $i) => [
            'pos' => $i + 1,
            'you' => (int) $r->referrer_id === $viewer->id,
            'name' => $this->boardName($users[$r->referrer_id] ?? null, (int) $r->referrer_id === $viewer->id),
            'grade' => $users[$r->referrer_id]->grade_num ?? null,
            'friends' => (int) $r->friends,
        ]);

        $top = $list->take(10)->values();
        $me = $list->firstWhere('you', true);

        return [
            'top' => $top,
            'me' => ($me && $me['pos'] > 10) ? $me : null,
            'totalFriends' => FriendInvite::whereNull('cancelled_at')
                ->where('first_lesson_on', '>=', $yearStart->toDateString())->count(),
        ];
    }

    /** Очередь выплат для преподавателя: доли и бонусы, которые ещё не выданы. */
    public function payoutQueue(): array
    {
        $credits = FriendInviteCredit::whereNull('paid_at')
            ->whereHas('invite', fn ($q) => $q->whereNotNull('qualified_at')->whereNull('cancelled_at'))
            ->with(['referrer:id,name,grade_num', 'invite.credits.referrer:id,name'])
            ->orderBy('id')->get();
        $bonuses = FriendInviteBonus::whereNull('paid_at')->with('referrer:id,name,grade_num')->orderBy('id')->get();

        return [
            'credits' => $credits,
            'bonuses' => $bonuses,
            'total' => (int) $credits->sum('amount') + (int) $bonuses->sum('amount'),
        ];
    }

    public function rub(int $amount): string
    {
        // Неразрывные пробелы: «2 000 ₽» не должно переноситься посреди числа.
        return number_format($amount, 0, ',', "\u{00A0}") . "\u{00A0}₽";
    }

    /** 1 друг, 2 друга, 5 друзей. */
    public function friendsWord(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        $word = match (true) {
            $mod10 === 1 && $mod100 !== 11 => 'друг',
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => 'друга',
            default => 'друзей',
        };

        return $n . ' ' . $word;
    }

    /** «пополам с Кириллом» / «на троих с Кириллом и Настей» — без склонения имён. */
    public function splitText(array $others): string
    {
        if (count($others) === 0) {
            return '';
        }

        return (count($others) === 1 ? 'пополам с: ' : 'на троих с: ') . implode(', ', $others);
    }

    /** «Иван Петров» → «Иван П.» */
    public function shortName(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (! $parts) {
            return '';
        }

        return count($parts) > 1 ? $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.' : $parts[0];
    }

    private function boardName(?User $user, bool $isViewer): string
    {
        if ($isViewer) {
            return 'Ты';
        }
        if ($user === null) {
            return 'Ученик';
        }
        if ($user->invite_board_hidden) {
            return $user->grade_num ? 'Ученик ' . $user->grade_num . ' класса' : 'Ученик';
        }

        return $this->shortName($user->name);
    }
}
