<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_5_saves_correctly(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'first_name'    => 'Тест',
                 'last_name'     => 'Тестов',
                 'grade_num'     => 5,
                 'grade_letter'  => 'А',
                 'school_number' => '1',
                 'city'          => 'Москва',
             ])->assertRedirect();

        $this->assertSame(5, $user->fresh()->grade_num);
    }

    public function test_grade_4_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'first_name'    => 'Тест',
                 'last_name'     => 'Тестов',
                 'grade_num'     => 4,
                 'grade_letter'  => 'А',
                 'school_number' => '1',
             ])->assertSessionHasErrors('grade_num');
    }

    public function test_letter_е_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)
             ->post('https://student.' . config('app.base_domain') . '/onboarding', [
                 'first_name'    => 'Тест',
                 'last_name'     => 'Тестов',
                 'grade_num'     => 7,
                 'grade_letter'  => 'Е',
                 'school_number' => '1',
                 'city'          => 'Москва',
             ])->assertRedirect();

        $this->assertSame('Е', $user->fresh()->grade_letter);
    }
}
