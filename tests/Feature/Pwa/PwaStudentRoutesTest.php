<?php

namespace Tests\Feature\Pwa;

use App\Models\UserGift;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PwaStudentRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_unauthenticated(): void
    {
        $response = $this->get('http://student.palomatika.ru/');
        $response->assertRedirect();
    }

    public function test_onboarding_page_accessible_when_authenticated(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);
        $response = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');
        $response->assertStatus(200);
    }

    public function test_pwa_and_miniapp_onboarding_use_identical_grade_letters(): void
    {
        $user = User::factory()->create(['oauth_provider' => 'vk', 'oauth_id' => '1']);

        $pwaResponse = $this->actingAs($user)->get('http://student.palomatika.ru/onboarding');
        $miniappResponse = $this->actingAs($user)->get('/tg/onboarding');

        $pwaResponse->assertOk();
        $miniappResponse->assertOk();

        $letters = "'А','Б','В','Г','Д','Е','К','М'";
        $pwaResponse->assertSee($letters, false);
        $miniappResponse->assertSee($letters, false);
    }

    public function test_student_can_mark_pending_gift_as_seen(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);

        $gift = UserGift::create([
            'user_id' => $user->id,
            'gift_type' => 'premium_months',
            'payload' => ['message' => 'Тестовый подарок'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('http://student.palomatika.ru/gift/seen', [
            'gift_id' => $gift->id,
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('user_gifts', [
            'id' => $gift->id,
            'shown_at' => null,
        ]);
    }

    public function test_seen_gift_no_longer_renders_on_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);

        $gift = UserGift::create([
            'user_id' => $user->id,
            'gift_type' => 'premium_months',
            'payload' => ['message' => 'Повторно не показывай'],
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/')
            ->assertOk()
            ->assertSee('Повторно не показывай');

        $this->actingAs($user)->postJson('http://student.palomatika.ru/gift/seen', [
            'gift_id' => $gift->id,
        ])->assertOk();

        $this->actingAs($user)
            ->get('http://student.palomatika.ru/')
            ->assertOk()
            ->assertDontSee('Повторно не показывай');
    }
}
