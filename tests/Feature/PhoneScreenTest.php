<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_details_are_kept_for_the_second_step(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '8 (900) 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call', function ($call) {
                return is_array($call) && $call['phone'] === '+7 900 123-45-67' && abs($call['at'] - time()) < 5;
            });
    }

    public function test_call_details_are_forgotten_once_the_code_is_accepted(): void
    {
        $this->fakeCall();
        $user = User::factory()->create();
        DB::table('phone_login_numbers')->insert(['phone' => '79001234567', 'user_id' => $user->id]);

        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call');
        $this->post('/phone-login', ['code' => '4567'])
            ->assertRedirect('https://student.'.config('app.base_domain').'/')
            ->assertSessionMissing('phone_call');
    }

    public function test_call_details_are_dropped_when_a_new_call_is_requested(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])
            ->assertRedirect('/phone-login')
            ->assertSessionHas('phone_call');

        $this->post('/phone-login', ['action' => 'start', 'phone' => '123'])
            ->assertSessionHasErrors()
            ->assertSessionMissing('phone_call');
    }

    public function test_wrong_code_error_tells_how_to_fix_it(): void
    {
        $this->fakeCall();
        $this->post('/phone-login', ['action' => 'start', 'phone' => '+7 900 123-45-67'])->assertRedirect('/phone-login');

        $this->post('/phone-login', ['code' => '0000'])->assertSessionHasErrors();
        $this->get('/phone-login')->assertOk()
            ->assertSee('Код не принят. Проверьте цифры или закажите звонок ещё раз.');
    }

    private function fakeCall(): void
    {
        config(['services.zvonok.id' => 'test-id', 'services.zvonok.key' => 'test-key']);
        Http::fake([
            'zvonok.com/manager/cabapi_external/api/v1/phones/flashcall/' => Http::response([
                'status' => 'ok', 'data' => ['pincode' => '4567', 'call_id' => 1],
            ]),
        ]);
    }
}
