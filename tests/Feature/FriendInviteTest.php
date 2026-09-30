<?php

namespace Tests\Feature;

use App\Models\FriendBoardEntry;
use App\Models\FriendInvite;
use App\Models\FriendInviteBonus;
use App\Models\FriendInviteCredit;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\FriendInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * «Позови друга»: 8–11 класс — 2000 ₽ за друга, оплатившего месяц, и +3000 ₽ за
 * каждого третьего; общий друг делит 2000 ₽ поровну. 6–7 класс — скидка 50%
 * на следующий месяц обоим. Доски зовущих ведёт супер-админ вручную.
 */
class FriendInviteTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private FriendInviteService $svc;
    private static int $chatId = 900000;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->svc = app(FriendInviteService::class);
    }

    private function student(string $name, int $grade = 9, bool $attached = true): User
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => $name,
            'grade_num' => $grade,
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => self::$chatId++,
        ]);
        if ($attached) {
            TeacherStudent::create(['teacher_id' => $this->teacher->id, 'student_id' => $student->id, 'source' => 'manual']);
        }

        return $student;
    }

    private function studentUrl(string $path = '/'): string
    {
        return 'https://student.' . config('app.base_domain') . $path;
    }

    private function teacherUrl(string $path): string
    {
        return 'https://teacher.' . config('app.base_domain') . $path;
    }

    private function bring(string $name, array $referrers, bool $qualify = true): FriendInvite
    {
        $invite = $this->svc->register($this->teacher, $name, 9, Carbon::today(), array_map(fn ($u) => $u->id, $referrers));
        if ($qualify) {
            $this->svc->qualify($invite, $this->teacher);
        }

        return $invite;
    }

    public function test_shares_split_2000_evenly_remainder_to_first(): void
    {
        $this->assertSame([2000], $this->svc->shares(1));
        $this->assertSame([1000, 1000], $this->svc->shares(2));
        $this->assertSame([668, 666, 666], $this->svc->shares(3));
    }

    public function test_strip_text_and_gate(): void
    {
        $ok = $this->student('Ваня Петров', 9);
        $this->actingAs($ok)->get($this->studentUrl())->assertOk()
            ->assertSee('Получи ' . $this->svc->rub(2000) . ' за приглашённого друга')
            ->assertDontSee('Живыми деньгами')
            ->assertSee('Подробнее')
            ->assertDontSee('Нет Premium');

        $sixth = $this->student('Петя Шестой', 6);
        $this->assertSame(FriendInviteService::DISCOUNT, $this->svc->programFor($sixth));
        $this->assertSame('Получи скидку 50% за приглашённого друга', $this->svc->strip($sixth)['title']);

        $fifth = $this->student('Коля Пятый', 5);
        $this->assertFalse($this->svc->isEligible($fifth));
        $this->actingAs($fifth)->get($this->studentUrl('/friends'))->assertNotFound();

        $stranger = $this->student('Чужой Человек', 9, attached: false);
        $this->assertFalse($this->svc->isEligible($stranger));
        $this->actingAs($stranger)->get($this->studentUrl())->assertOk()->assertDontSee('за приглашённого друга');
    }

    public function test_oge_countdown_uses_config_date(): void
    {
        config(['palomatika.oge_exam_at' => '2027-06-01T10:00:00+03:00']);
        $this->actingAs($this->student('Ваня Петров'))->get($this->studentUrl())
            ->assertOk()->assertSee('2027-06-01T10:00:00+03:00', false)->assertDontSee('2026-06-02', false);
    }

    public function test_cash_page_emphasises_third_friend(): void
    {
        $vanya = $this->student('Ваня Петров');
        $this->actingAs($vanya)->get($this->studentUrl('/friends'))->assertOk()
            ->assertSee('Третий друг')
            ->assertSee('+ ' . $this->svc->rub(3000) . ' сверху')
            ->assertSee('= ' . $this->svc->rub(5000) . ' за одного друга')
            ->assertSee($this->svc->rub(9000))
            ->assertSee('Если друг решит заниматься с нами')
            ->assertSee('Позвали вместе — делите приз')
            ->assertSee('по ' . $this->svc->rub(1000))
            ->assertSee('по ~' . $this->svc->rub(667))
            ->assertSee('Получишь деньги, когда он оплатит месяц занятий')
            ->assertDontSee('Бонус за ' . $this->svc->rub(6000))
            ->assertDontSee('Отметить, кого позвал')
            ->assertDontSee('Что сказать')
            ->assertDontSee('Кому сказать');

        $this->bring('Дима Коршунов', [$vanya]);
        $this->bring('Саша Волков', [$vanya], qualify: false);
        $this->actingAs($vanya)->get($this->studentUrl('/friends'))->assertOk()
            ->assertSee('Дима К.')->assertSee('ждём оплату месяца')->assertSee('Ещё 2 друга');
        $this->actingAs($vanya)->get($this->studentUrl())
            ->assertSee('Саша В. пришёл на первое занятие')->assertSee('Оплатит месяц');
    }

    public function test_every_third_solo_friend_brings_bonus_once(): void
    {
        $vanya = $this->student('Ваня Петров');
        $this->bring('Друг Один', [$vanya]);
        $this->bring('Друг Два', [$vanya]);
        $this->assertSame(0, FriendInviteBonus::count());

        $third = $this->bring('Друг Три', [$vanya]);
        $this->assertSame(1, FriendInviteBonus::where('referrer_id', $vanya->id)->where('amount', 3000)->count());

        $this->svc->qualify($third->fresh(), $this->teacher);
        $this->svc->syncBonuses($vanya->id);
        $this->assertSame(1, FriendInviteBonus::count());
        $this->assertSame(9000, $this->svc->summary($vanya)['total']);
        $this->actingAs($vanya)->get($this->studentUrl('/friends'))->assertSee('Бонус за 3-го друга');
    }

    public function test_splitting_does_not_let_two_students_farm_the_bonus(): void
    {
        $a = $this->student('Ваня Петров');
        $b = $this->student('Кирилл Минин');
        foreach (['Один', 'Два', 'Три'] as $n) {
            $this->bring("Друг $n", [$a, $b]);
        }
        $this->assertSame(3000, $this->svc->earned($a->id));
        $this->assertSame(0, FriendInviteBonus::count());
    }

    public function test_discount_program_for_grades_6_7(): void
    {
        $petya = $this->student('Петя Шестой', 6);
        $masha = $this->student('Маша Седьмая', 7);
        $vanya = $this->student('Ваня Петров', 9);

        $this->actingAs($petya)->get($this->studentUrl('/friends'))->assertOk()
            ->assertSee('−50%')->assertSee('Расскажи родителям')->assertSee('Для родителей')
            ->assertDontSee('Третий друг');

        // 6–7 и 8–11 в одной записи смешивать нельзя
        $this->actingAs($this->teacher)->post($this->teacherUrl('/friends'), [
            'invitee_name' => 'Новый Друг', 'first_lesson_on' => '2026-09-26', 'referrers' => [$petya->id, $vanya->id],
        ])->assertSessionHas('friends_error');

        $invite = $this->bring('Лёва Новый', [$petya, $masha], qualify: false);
        $this->assertSame(FriendInviteService::DISCOUNT, $invite->program);
        $this->assertSame([50, 50], $invite->credits()->orderBy('id')->pluck('amount')->all());
        $this->svc->qualify($invite, $this->teacher);

        $this->assertSame(0, FriendInviteBonus::count());
        $this->assertSame(0, $this->svc->earned($petya->id));
        $queue = $this->svc->payoutQueue();
        $this->assertSame(0, $queue['total']);
        $this->assertCount(2, $queue['discounts']);
        $this->assertCount(1, $queue['inviteeDiscounts']);

        $this->actingAs($this->teacher)->post($this->teacherUrl("/friends/{$invite->id}/invitee-discount"))->assertRedirect();
        $this->assertNotNull($invite->fresh()->invitee_discount_applied_at);
        $this->assertSame('У тебя скидка 50% на месяц', app(FriendInviteService::class)->strip($petya->fresh())['title']);
    }

    public function test_cancelled_friend_earns_nothing(): void
    {
        $vanya = $this->student('Ваня Петров');
        $invite = $this->bring('Не Остался', [$vanya], qualify: false);
        $this->svc->cancel($invite);
        $this->svc->qualify($invite->fresh(), $this->teacher);
        $this->assertSame(0, $this->svc->earned($vanya->id));
    }

    public function test_teacher_registers_qualifies_and_pays_out(): void
    {
        $vanya = $this->student('Ваня Петров');
        $nastya = $this->student('Настя Волкова', 10);
        $fifth = $this->student('Коля Пятый', 5);

        $this->actingAs($this->teacher)->get($this->teacherUrl('/friends'))
            ->assertOk()->assertSee('Ваня Петров')->assertDontSee('Коля Пятый')->assertDontSee('Доски зовущих');

        $this->actingAs($this->teacher)->post($this->teacherUrl('/friends'), [
            'invitee_name' => 'Саша Волков', 'first_lesson_on' => '2026-09-26', 'referrers' => [$fifth->id],
        ])->assertSessionHas('friends_error');
        $this->assertSame(0, FriendInvite::count());

        $this->actingAs($this->teacher)->post($this->teacherUrl('/friends'), [
            'invitee_name' => 'Саша Волков', 'invitee_grade' => 9, 'first_lesson_on' => '2026-09-26',
            'referrers' => [$vanya->id, $nastya->id],
        ])->assertSessionHas('friends_ok');
        $invite = FriendInvite::firstOrFail();
        $this->assertSame([1000, 1000], $invite->credits()->orderBy('id')->pluck('amount')->all());

        $this->actingAs($this->teacher)->post($this->teacherUrl("/friends/{$invite->id}/qualify"))->assertRedirect();
        $this->assertSame(2000, $this->svc->payoutQueue()['total']);

        $credit = FriendInviteCredit::where('referrer_id', $vanya->id)->firstOrFail();
        $this->actingAs($this->teacher)->post($this->teacherUrl("/friends/credits/{$credit->id}/paid"))->assertRedirect();
        $this->assertSame(1000, $this->svc->payoutQueue()['total']);
    }

    public function test_board_is_manual_and_only_super_admin_edits_it(): void
    {
        $vanya = $this->student('Ваня Петров', 9);
        $kirill = $this->student('Кирилл Минин', 10);
        $petya = $this->student('Петя Шестой', 6);

        // автоматически доска не заполняется
        $this->bring('Друг Вани', [$vanya]);
        $this->assertTrue($this->svc->board(FriendInviteService::CASH, $vanya)['top']->isEmpty());

        // обычный учитель и простой админ — нельзя
        $this->actingAs($this->teacher)->post($this->teacherUrl('/friends/board'), [
            'program' => 'cash', 'user_id' => $vanya->id, 'friends' => 3,
        ])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        config(['palomatika.super_admin_ids' => [999999]]);
        $this->actingAs($admin)->post($this->teacherUrl('/friends/board'), [
            'program' => 'cash', 'user_id' => $vanya->id, 'friends' => 3,
        ])->assertForbidden();

        config(['palomatika.super_admin_ids' => [$admin->id]]);
        $this->actingAs($admin)->get($this->teacherUrl('/friends'))->assertOk()->assertSee('Доски зовущих');
        foreach ([[$vanya, 3], [$kirill, 1]] as [$u, $n]) {
            $this->actingAs($admin)->post($this->teacherUrl('/friends/board'), [
                'program' => 'cash', 'user_id' => $u->id, 'friends' => $n,
            ])->assertSessionHas('friends_ok');
        }
        // шестиклассника на доску 8–11 не добавить
        $this->actingAs($admin)->post($this->teacherUrl('/friends/board'), [
            'program' => 'cash', 'user_id' => $petya->id, 'friends' => 1,
        ])->assertSessionHas('friends_error');
        $this->actingAs($admin)->post($this->teacherUrl('/friends/board'), [
            'program' => 'discount', 'user_id' => $petya->id, 'friends' => 2,
        ])->assertSessionHas('friends_ok');

        $board = $this->svc->board(FriendInviteService::CASH, $kirill);
        $this->assertSame(['Ваня П.', 'Ты'], $board['top']->pluck('name')->all());
        $this->assertSame(4, $board['totalFriends']);
        $this->assertSame([2], $this->svc->board(FriendInviteService::DISCOUNT, $petya)['top']->pluck('friends')->all());

        // обновление числа и удаление
        $this->actingAs($admin)->post($this->teacherUrl('/friends/board'), [
            'program' => 'cash', 'user_id' => $kirill->id, 'friends' => 5,
        ]);
        $this->assertSame('Кирилл М.', $this->svc->board(FriendInviteService::CASH, $vanya)['top'][0]['name']);
        $entry = FriendBoardEntry::where('user_id', $kirill->id)->firstOrFail();
        $this->actingAs($admin)->delete($this->teacherUrl("/friends/board/{$entry->id}"))->assertRedirect();
        $this->assertSame(1, FriendBoardEntry::where('program', 'cash')->count());

        // скрытое имя
        $this->actingAs($vanya)->post($this->studentUrl('/friends/board'), ['show' => 0]);
        $this->assertSame('Ученик 9 класса', $this->svc->board(FriendInviteService::CASH, $kirill)['top'][0]['name']);
    }

    public function test_strip_on_vpr_and_ege_dashboards(): void
    {
        $eighth = $this->student('Лёша Восьмой', 8);
        $this->actingAs($eighth)->get(route('pwa.student.vpr.home'))->assertOk()
            ->assertSee('за приглашённого друга')->assertDontSee('Нет Premium');

        $eleventh = $this->student('Оля Одиннадцатая', 11);
        $this->actingAs($eleventh)->get(route('pwa.student.ege.home'))->assertOk()
            ->assertSee('за приглашённого друга')->assertDontSee('Нет Premium');
    }

    public function test_russian_plurals(): void
    {
        $this->assertSame('1 друг', $this->svc->friendsWord(1));
        $this->assertSame('3 друга', $this->svc->friendsWord(3));
        $this->assertSame('5 друзей', $this->svc->friendsWord(5));
        $this->assertSame('11 друзей', $this->svc->friendsWord(11));
        $this->assertSame('21 друг', $this->svc->friendsWord(21));
    }
}
