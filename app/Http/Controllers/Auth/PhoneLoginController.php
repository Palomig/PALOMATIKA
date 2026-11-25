<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PhoneLoginController extends Controller
{
    public function form(Request $request)
    {
        abort_unless(config('services.zvonok.key') && config('services.zvonok.id'), 404);
        return response()->view('auth.phone', [
            'pending' => $request->session()->has('phone_challenge'),
            'call' => $request->session()->get('phone_call'),
        ])->header('Cache-Control', 'no-store');
    }

    public function submit(Request $request)
    {
        abort_unless(config('services.zvonok.key') && config('services.zvonok.id'), 404);
        $actor = (int)Auth::id();
        $service = new PhoneLogin(DB::connection(), config('services.zvonok.id'), config('services.zvonok.key'));
        if ($request->input('action') === 'start') {
            $data = $request->validate(['phone' => 'required|string|max:40']);
            $request->session()->forget(['phone_challenge', 'phone_call']);
            try {
                $challenge = $service->start($data['phone'], $request->ip(), $actor);
            } catch (\DomainException $exception) {
                return back()->withErrors($exception->getMessage());
            }
            $request->session()->put('phone_challenge', $challenge);
            $request->session()->put('phone_call', ['phone' => self::format($data['phone']), 'at' => time()]);
            return redirect('/phone-login');
        }
        $data = $request->validate(['code' => 'required|string|regex:/^[0-9]{4}$/D']);
        $id = $service->verify((string)$request->session()->get('phone_challenge'), $data['code'], $actor);
        $user = $id ? User::find($id) : null;
        if (!$user) {
            return back()->withErrors('Код не принят. Проверьте цифры или закажите звонок ещё раз.');
        }
        $request->session()->forget(['phone_challenge', 'phone_call']);
        if (!$actor) {
            Auth::login($user, true);
            $request->session()->regenerate();
        }
        $domain = config('app.base_domain');
        $url = match ($user->role) {
            'parent' => "https://".$domain."/parent/dashboard",
            'teacher', 'admin' => "https://teacher.".$domain."/dashboard",
            default => "https://student.".$domain."/",
        };
        return redirect()->away($url);
    }

    private static function format(string $phone): string
    {
        $digits = substr(preg_replace('/\D/', '', $phone), -10);
        return '+7 '.substr($digits, 0, 3).' '.substr($digits, 3, 3).'-'.substr($digits, 6, 2).'-'.substr($digits, 8);
    }
}
