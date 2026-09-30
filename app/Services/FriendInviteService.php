<?php

namespace App\Services;

use App\Models\FriendBoardEntry;
use App\Models\FriendInvite;
use App\Models\FriendInviteBonus;
use App\Models\FriendInviteCredit;
use App\Models\TeacherStudent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * «Позови друга» — две акции, у каждой своя страница и своя доска.
 *
 * cash (8–11 класс): 2000 ₽ наличными за друга, который оплатил месяц занятий;
 * за каждого третьего друга ещё +3000 ₽ сверху. Общий друг делит 2000 ₽ поровну,
 * поэтому порог бонуса в коде считается в рублях (каждые 6000 ₽): иначе сплит
 * превращается в сговор «записываем друг друга на всех». На экране — «третий друг».
 *
 * discount (6–7 класс): рассказать родителям; друг оплатил месяц — скидка 50%
 * на следующий месяц и пригласившему, и приглашённому.
 */
class FriendInviteService
{
    public const CASH = 'cash';
    public const DISCOUNT = 'discount';

    public const REWARD = 2000;
    public const BONUS = 3000;
    public const BONUS_EVERY = 3;
    public const BONUS_STEP = self::REWARD * self::BONUS_EVERY;
    public const DISCOUNT_PERCENT = 50;
    public const MAX_REFERRERS = 3;

    public const GRADES = [
        self::CASH => [8, 9, 10, 11],
        self::DISCOUNT => [6, 7],
    ];

    /** @var array<int, array> полоска считается и для дашборда, и для самой строки */
    private array $stripCache = [];

    /**
     * Акция ученика по классу. Видна только прикреплённым к учителю: Паломатикой
     * пользуются посторонние через открытые банки, им её показывать нельзя.
     * Админ видит акцию 8–11 как превью.
     */
    public function programFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }
        if ($user->isAdmin()) {
            return self::CASH;
        }
        if ($user->role !== 'student') {
            return null;
        }
        $program = $this->programForGrade((int) $user->grade_num);
        if ($program === null || ! TeacherStudent::where('student_id', $user->id)->exists()) {
            return null;
        }

        return $program;
    }

    public function isEligible(?User $user): bool
    {
        return $this->programFor($user) !== null;
    }

    public function programForGrade(int $grade): ?string
    {
        foreach (self::GRADES as $program => $grades) {
            if (in_array($grade, $grades, true)) {
                return $program;
            }
        }

        return null;
    }

    /** Ученики, которых преподаватель может отметить как пригласивших (6–11 класс). */
    public function eligibleReferrers(?int $teacherId = null, ?string $program = null): Collection
    {
        $grades = $program ? self::GRADES[$program] : array_merge(...array_values(self::GRADES));
        $links = TeacherStudent::query()
            ->whereHas('student', fn ($q) => $q->where('role', 'student')->whereIn('grade_num', $grades))
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
     * Акция определяется по классу пригласивших; смешивать 6–7 и 8–11 нельзя.
     *
     * @param  int[]  $referrerIds
     */
    public function register(User $teacher, string $name, ?int $grade, CarbonInterface $firstLessonOn, array $referrerIds): FriendInvite
    {
        $referrerIds = array_values(array_unique(array_map('intval', $referrerIds)));
        if (count($referrerIds) < 1 || count($referrerIds) > self::MAX_REFERRERS) {
            throw new InvalidArgumentException('Пригласивших должно быть от 1 до ' . self::MAX_REFERRERS);
        }
        $allowed = $this->eligibleReferrers()->keyBy('id');
        $programs = [];
        foreach ($referrerIds as $id) {
            if (! $allowed->has($id)) {
                throw new InvalidArgumentException('Пригласивший не участвует в акции');
            }
            $programs[] = $this->programForGrade((int) $allowed[$id]->grade_num);
        }
        $programs = array_unique($programs);
        if (count($programs) > 1) {
            throw new InvalidArgumentException('У 6–7 и 8–11 классов разные акции — отметь пригласивших из одной группы');
        }
        $program = $programs[0];
        // Скидка не делится: каждому пригласившему — свои 50% на следующий месяц.
        $amounts = $program === self::CASH
            ? $this->shares(count($referrerIds))
            : array_fill(0, count($referrerIds), self::DISCOUNT_PERCENT);

        return DB::transaction(function () use ($teacher, $name, $grade, $firstLessonOn, $referrerIds, $amounts, $program) {
            $invite = FriendInvite::create([
                'program' => $program,
                'invitee_name' => trim($name),
                'invitee_grade' => $grade,
                'first_lesson_on' => $firstLessonOn->toDateString(),
                'registered_by' => $teacher->id,
            ]);
            foreach ($referrerIds as $i => $referrerId) {
                $invite->credits()->create(['referrer_id' => $referrerId, 'amount' => $amounts[$i]]);
            }

            return $invite;
        });
    }

    /** Друг оплатил месяц занятий — начислено, бонусы досчитаны. */
    public function qualify(FriendInvite $invite, User $teacher): void
    {
        if (! $invite->isPending()) {
            return;
        }
        DB::transaction(function () use ($invite, $teacher) {
            $invite->update(['qualified_at' => now(), 'qualified_by' => $teacher->id]);
            if ($invite->program === self::CASH) {
                foreach ($invite->credits()->pluck('referrer_id') as $referrerId) {
                    $this->syncBonuses((int) $referrerId);
                }
            }
        });
    }

    public function cancel(FriendInvite $invite): void
    {
        if ($invite->isPending()) {
            $invite->update(['cancelled_at' => now()]);
        }
    }

    /** Заработано (акция cash): доли по друзьям, оплатившим месяц. */
    public function earned(int $referrerId): int
    {
        return (int) FriendInviteCredit::where('referrer_id', $referrerId)
            ->whereHas('invite', fn ($q) => $q->where('program', self::CASH)
                ->whereNotNull('qualified_at')->whereNull('cancelled_at'))
            ->sum('amount');
    }

    /** Создаёт недостающие бонусы за каждого третьего друга. Созданные не трогает. */
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
                'program' => $c->invite->program,
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
        $inCycle = $earned % self::BONUS_STEP;
        // Прогресс до бонуса в «друзьях»: общий друг — половина или треть.
        $friendsInCycle = $inCycle / self::REWARD;

        return [
            'earned' => $earned,
            'bonusTotal' => (int) $bonuses->sum('amount'),
            'total' => $earned + (int) $bonuses->sum('amount'),
            'friendsInCycle' => $friendsInCycle,
            'friendsToBonus' => (int) ceil((self::BONUS_STEP - $inCycle) / self::REWARD),
            'friends' => $friends,
            'pending' => $friends->where('qualified', false)->values(),
            'discounts' => $friends->where('program', self::DISCOUNT)->where('qualified', true)->values(),
            'bonuses' => $bonuses,
        ];
    }

    /** Полоска под кнопкой УРОК: новость важнее суммы, сумма важнее оффера. */
    public function strip(User $user): array
    {
        return $this->stripCache[$user->id] ??= $this->buildStrip($user);
    }

    private function buildStrip(User $user): array
    {
        $program = $this->programFor($user);
        $s = $this->summary($user);
        $pending = $s['pending']->first();

        if ($program === self::DISCOUNT) {
            if ($pending) {
                return $this->stripData('warm', '🔥', $pending['name'] . ' пришёл на первое занятие', 'Оплатит месяц — вам обоим скидка ' . self::DISCOUNT_PERCENT . '%');
            }
            if ($s['discounts']->isNotEmpty()) {
                return $this->stripData('gold', '🏆', 'У тебя скидка ' . self::DISCOUNT_PERCENT . '% на месяц', 'Приведи ещё друга — будет ещё месяц со скидкой');
            }

            return $this->stripData('', '🎁', 'Получи скидку ' . self::DISCOUNT_PERCENT . '% за приглашённого друга', '');
        }

        if ($pending) {
            return $this->stripData('warm', '🔥', $pending['name'] . ' пришёл на первое занятие', 'Оплатит месяц — ' . $this->rub($pending['amount']) . ' твои');
        }
        if ($s['earned'] > 0) {
            $left = $s['friendsToBonus'];

            return $this->stripData('gold', '🏆', 'Заработано ' . $this->rub($s['total']),
                'Ещё ' . $this->friendsWord($left) . ' — и +' . $this->rub(self::BONUS) . ' сверху');
        }

        return $this->stripData('', '🎁', 'Получи ' . $this->rub(self::REWARD) . ' за приглашённого друга', '');
    }

    private function stripData(string $tone, string $icon, string $title, string $sub): array
    {
        return compact('tone', 'icon', 'title', 'sub');
    }

    /**
     * Доска зовущих: ведёт супер-админ вручную, своя у каждой акции.
     * Показываем количество друзей, денег на доске нет.
     */
    public function board(string $program, User $viewer): array
    {
        $entries = FriendBoardEntry::where('program', $program)
            ->where('friends', '>', 0)
            ->with('user:id,name,grade_num')
            ->orderByDesc('friends')->orderBy('id')
            ->get();

        $list = $entries->values()->map(fn (FriendBoardEntry $e, $i) => [
            'pos' => $i + 1,
            'you' => $e->user_id === $viewer->id,
            'name' => $this->boardName($e->user, $e->user_id === $viewer->id),
            'grade' => $e->user?->grade_num,
            'friends' => (int) $e->friends,
        ]);
        $me = $list->firstWhere('you', true);

        return [
            'top' => $list->take(10)->values(),
            'me' => ($me && $me['pos'] > 10) ? $me : null,
            'totalFriends' => (int) $entries->sum('friends'),
        ];
    }

    /**
     * Строка доски зовущих: добавить ученика или поменять число друзей.
     * Общая точка для кабинета супер-админа и команды friends:board-set.
     */
    public function setBoardEntry(string $program, int $userId, int $friends): FriendBoardEntry
    {
        if (! array_key_exists($program, self::GRADES)) {
            throw new InvalidArgumentException('Неизвестная акция: ' . $program);
        }
        if ($friends < 0 || $friends > 99) {
            throw new InvalidArgumentException('Число друзей — от 0 до 99');
        }
        $user = User::find($userId);
        if ($user === null) {
            throw new InvalidArgumentException('Ученик не найден');
        }
        if (! $this->eligibleReferrers(null, $program)->contains('id', $userId)) {
            $grades = self::GRADES[$program];
            throw new InvalidArgumentException(sprintf(
                '%s не участвует в акции %d–%d класса: %s',
                $user->name, min($grades), max($grades),
                $user->grade_num ? $user->grade_num . ' класс' . (TeacherStudent::where('student_id', $userId)->exists() ? '' : ', не прикреплён к учителю') : 'не указан класс'
            ));
        }

        return FriendBoardEntry::updateOrCreate(
            ['program' => $program, 'user_id' => $userId],
            ['friends' => $friends]
        );
    }

    /** Очередь для преподавателя: деньги к выдаче и скидки к применению. */
    public function payoutQueue(): array
    {
        $qualified = fn ($program) => fn ($q) => $q->where('program', $program)
            ->whereNotNull('qualified_at')->whereNull('cancelled_at');

        $credits = FriendInviteCredit::whereNull('paid_at')
            ->whereHas('invite', $qualified(self::CASH))
            ->with(['referrer:id,name,grade_num', 'invite.credits.referrer:id,name'])
            ->orderBy('id')->get();
        $bonuses = FriendInviteBonus::whereNull('paid_at')->with('referrer:id,name,grade_num')->orderBy('id')->get();

        $discounts = FriendInviteCredit::whereNull('paid_at')
            ->whereHas('invite', $qualified(self::DISCOUNT))
            ->with(['referrer:id,name,grade_num', 'invite:id,invitee_name'])
            ->orderBy('id')->get();
        $inviteeDiscounts = FriendInvite::where('program', self::DISCOUNT)
            ->whereNotNull('qualified_at')->whereNull('cancelled_at')
            ->whereNull('invitee_discount_applied_at')
            ->orderBy('id')->get();

        return [
            'credits' => $credits,
            'bonuses' => $bonuses,
            'total' => (int) $credits->sum('amount') + (int) $bonuses->sum('amount'),
            'discounts' => $discounts,
            'inviteeDiscounts' => $inviteeDiscounts,
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

    /** «пополам с: Кирилл М.» / «на троих с: …» — без склонения имён. */
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

        return $this->shortName($user->name);
    }
}
