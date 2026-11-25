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
        abort_unless(config('services.plusofon.key') && config('services.plusofon.id'), 404);
        return response()->view('auth.phone', ['pending' => $request->session()->has('phone_challenge')])
            ->header('Cache-Control', 'no-store');
    }

    public function submit(Request $request)
    {
        abort_unless(config('services.plusofon.key') && config('services.plusofon.id'), 404);
        $actor = (int)Auth::id();
        $service = new PhoneLogin(DB::connection(), config('services.plusofon.id'), config('services.plusofon.key'));
        if ($request->input('action') === 'start') {
            $data = $request->validate(['phone' => 'required|string|max:40']);
            $request->session()->forget('phone_challenge');
            try {
                $challenge = $service->start($data['phone'], $request->ip(), $actor);
            } catch (\DomainException $exception) {
                return back()->withErrors($exception->getMessage());
            }
            $request->session()->put('phone_challenge', $challenge);
            return redirect('/phone-login');
        }
        $data = $request->validate(['code' => 'required|string|regex:/^[0-9]{4}$/D']);
        $id = $service->verify((string)$request->session()->get('phone_challenge'), $data['code'], $actor);
        $user = $id ? User::find($id) : null;
        if (!$user) {
            return back()->withErrors('Код не принят. Проверьте цифры и привязку номера.');
        }
        $request->session()->forget('phone_challenge');
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
}
