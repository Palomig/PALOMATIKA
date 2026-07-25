<?php

namespace Tests\Feature;

use App\Models\HomeworkAssignment;
use App\Models\Homework;
use App\Models\User;
use App\Services\AccountMergeService;
use App\Services\TelegramIdentityResolver;
use App\Services\TelegramLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Мини-апп и веб-OIDC — РАЗНЫЕ ключи (настоящий chat_id против псевдонима),
 * поэтому сами по себе в один аккаунт не сходятся. Сводит их привязка через бота.
 */
class TelegramUnifiedIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_miniapp_and_oidc_are_separate_until_linked(): void
    {
        $resolver = app(TelegramIdentityResolver::class);

        $fromMiniApp = $resolver->resolveByChatId(['id' => 50050, 'username' => 'student1', 'name' => 'Ученик']);
        $fromWeb = $resolver->resolveBySub(['sub' => '16549735672622918414', 'username' => 'student1', 'name' => 'Ученик']);

        $this->assertNotSame($fromMiniApp->id, $fromWeb->id);
        $this->assertNull($fromWeb->telegram_chat_id);
    }

    public function test_linking_merges_oidc_account_into_miniapp_account(): void
    {
        $resolver = app(TelegramIdentityResolver::class);
        $links = app(TelegramLinkService::class);

        $miniApp = $resolver->resolveByChatId(['id' => 50050, 'username' => 'student1', 'name' => 'Ученик']);
        $miniApp->update(['role' => 'student']);
        $web = $resolver->resolveBySub(['sub' => '16549735672622918414', 'username' => 'student1', 'name' => 'Ученик']);
        $web->update(['role' => 'student']);

        // ДЗ выдали на «веб-двойника» — после слияния оно должно оказаться у канонического.
        $teacher = User::factory()->create(['role' => 'teacher']);
        $homework = Homework::create([
            'teacher_id' => $teacher->id, 'homework_type' => 'topic_photo_practice',
            'title' => 'ДЗ', 'tasks_count' => 2, 'topic_number' => 6, 'assigned_at' => now(),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $homework->id, 'student_id' => $web->id,
            'status' => 'assigned', 'tasks_total' => 2,
        ]);

        $code = $links->issueCode($web)['code'];
        $result = $links->completeLink($code, ['id' => 50050, 'username' => 'student1']);

        $this->assertNotNull($result);
        $this->assertTrue($result['merged']);
        $this->assertSame($miniApp->id, $result['user']->id);
        $this->assertSame(50050, $result['user']->telegram_chat_id);

        $this->assertSame(1, HomeworkAssignment::where('student_id', $miniApp->id)->count());
        $this->assertSame(0, HomeworkAssignment::where('student_id', $web->id)->count());

        // Донор остаётся в базе, но перестаёт быть входной точкой.
        $this->assertSame($miniApp->id, $web->fresh()->merged_into_id);
        $this->assertNull($web->fresh()->telegram_oidc_sub);
    }

    public function test_link_gives_chat_id_to_yandex_account_without_merge(): void
    {
        $links = app(TelegramLinkService::class);
        $student = User::factory()->create([
            'role' => 'student', 'oauth_provider' => 'yandex', 'oauth_id' => '977353831',
        ]);

        $code = $links->issueCode($student)['code'];
        $result = $links->completeLink($code, ['id' => 6490457130, 'username' => 'omae']);

        $this->assertFalse($result['merged']);
        $this->assertSame($student->id, $result['user']->id);
        $this->assertSame(6490457130, $student->fresh()->telegram_chat_id);
        // Вход остался яндексовым — мы добавили канал уведомлений, а не сменили провайдера.
        $this->assertSame('yandex', $student->fresh()->oauth_provider);
    }

    public function test_expired_code_is_rejected(): void
    {
        $links = app(TelegramLinkService::class);

        $this->assertNull($links->completeLink('nosuchcode0000000000', ['id' => 42]));
    }

    public function test_admin_stays_canonical_when_merging_with_student_duplicate(): void
    {
        $merger = app(AccountMergeService::class);

        $admin = User::factory()->create(['role' => 'admin', 'created_at' => now()->subYear()]);
        $studentDouble = User::factory()->create(['role' => 'student', 'created_at' => now()]);

        $this->assertSame($admin->id, $merger->pickCanonical($studentDouble, $admin)->id);
    }
}
