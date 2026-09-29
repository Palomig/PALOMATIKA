<?php

namespace Tests\Feature;

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
 * «Позови друга»: 2000 ₽ за друга, +3000 ₽ за каждые 6000 ₽ заработанного,
 * общий друг делит 2000 ₽ поровну. Видно только 8–11 классу у учителя.
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

    private function student_url(string $path = '/'): string
    {
        return 'https://student.' . config('app.base_domain') . $path;
    }

    private function teacher_url(string $path): string
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

    public function test_strip_visible_only_to_attached_students_of_grades_8_to_11(): void
    {
        $ok = $this->student('Ваня Петров', 9);
        $this->actingAs($ok)->get($this->student_url())->assertOk()->assertSee('Позови друга');

        $young = $this->student('Петя Малый', 7);
        $this->assertFalse($this->svc->isEligible($young));
        $this->actingAs($young)->get($this->student_url('/friends'))->assertNotFound();

        $stranger = $this->student('Чужой Человек', 9, attached: false);
        $this->assertFalse($this->svc->isEligible($stranger));
        $this->actingAs($stranger)->get($this->student_url())->assertOk()->assertDontSee('Позови друга');
    }

    public function test_friends_page_renders_empty_and_with_progress(): void
    {
        $vanya = $this->student('Ваня Петров');
        $this->actingAs($vanya)->get($this->student_url('/friends'))
            ->assertOk()->assertSee('Пока никто никого не позвал')->assertSee('Отметить, кого позвал');

        $this->bring('Дима Коршунов', [$vanya]);
        $this->bring('Саша Волков', [$vanya], qualify: false);

        $this->actingAs($vanya)->get($this->student_url('/friends'))
            ->assertOk()->assertSee('Дима К.')->assertSee('ждём второе')->assertSee('2 друга', false);
        $this->actingAs($vanya)->get($this->student_url())
            ->assertSee('Саша В. пришёл на первое занятие');
    }

    public function test_every_third_solo_friend_brings_bonus_once(): void
    {
        $vanya = $this->student('Ваня Петров');
        $this->bring('Друг Один', [$vanya]);
        $this->bring('Друг Два', [$vanya]);
        $this->assertSame(0, FriendInviteBonus::count());

        $third = $this->bring('Друг Три', [$vanya]);
        $this->assertSame(6000, $this->svc->earned($vanya->id));
        $this->assertSame(1, FriendInviteBonus::where('referrer_id', $vanya->id)->where('amount', 3000)->count());

        // повторная отметка не плодит бонусы
        $this->svc->qualify($third->fresh(), $this->teacher);
        $this->svc->syncBonuses($vanya->id);
        $this->assertSame(1, FriendInviteBonus::count());
        $this->assertSame(9000, $this->svc->summary($vanya)['total']);
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

        // на доске общий друг засчитан каждому целиком
        $board = $this->svc->board($a);
        $this->assertSame([3, 3], $board['top']->pluck('friends')->all());
        $this->assertSame(3, $board['totalFriends']);
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
        $young = $this->student('Петя Малый', 7);

        $this->actingAs($this->teacher)->get($this->teacher_url('/friends'))
            ->assertOk()->assertSee('Ваня Петров')->assertDontSee('Петя Малый');

        // 7 класс в акции не участвует
        $this->actingAs($this->teacher)->post($this->teacher_url('/friends'), [
            'invitee_name' => 'Саша Волков', 'first_lesson_on' => '2026-09-26', 'referrers' => [$young->id],
        ])->assertSessionHas('friends_error');
        $this->assertSame(0, FriendInvite::count());

        $this->actingAs($this->teacher)->post($this->teacher_url('/friends'), [
            'invitee_name' => 'Саша Волков', 'invitee_grade' => 9, 'first_lesson_on' => '2026-09-26',
            'referrers' => [$vanya->id, $nastya->id],
        ])->assertSessionHas('friends_ok');
        $invite = FriendInvite::firstOrFail();
        $this->assertSame([1000, 1000], $invite->credits()->orderBy('id')->pluck('amount')->all());
        $this->assertSame(0, $this->svc->payoutQueue()['total']);

        $this->actingAs($this->teacher)->post($this->teacher_url("/friends/{$invite->id}/qualify"))->assertRedirect();
        $this->assertSame(2000, $this->svc->payoutQueue()['total']);

        $credit = FriendInviteCredit::where('referrer_id', $vanya->id)->firstOrFail();
        $this->actingAs($this->teacher)->post($this->teacher_url("/friends/credits/{$credit->id}/paid"))->assertRedirect();
        $this->assertNotNull($credit->fresh()->paid_at);
        $this->assertSame(1000, $this->svc->payoutQueue()['total']);
    }

    public function test_student_notes_are_capped_and_board_name_can_be_hidden(): void
    {
        $vanya = $this->student('Ваня Петров');
        foreach (range(1, 6) as $i) {
            $this->actingAs($vanya)->post($this->student_url('/friends/notes'), ['name' => "Друг $i"]);
        }
        $this->assertSame(FriendInviteService::MAX_OPEN_NOTES, \App\Models\FriendInviteNote::where('user_id', $vanya->id)->count());

        $kirill = $this->student('Кирилл Минин', 10);
        $this->bring('Друг Кирилла', [$kirill], qualify: false);
        $this->actingAs($kirill)->post($this->student_url('/friends/board'), ['show' => 0]);
        $this->assertTrue((bool) $kirill->fresh()->invite_board_hidden);
        $this->assertSame('Ученик 10 класса', $this->svc->board($vanya)['top'][0]['name']);
    }

    public function test_strip_on_vpr_and_ege_dashboards(): void
    {
        $eighth = $this->student('Лёша Восьмой', 8);
        $this->actingAs($eighth)->get(route('pwa.student.vpr.home'))->assertOk()->assertSee('Позови друга');

        $eleventh = $this->student('Оля Одиннадцатая', 11);
        $this->actingAs($eleventh)->get(route('pwa.student.ege.home'))->assertOk()->assertSee('Позови друга');
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
