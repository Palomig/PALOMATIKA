<?php

namespace App\Http\Controllers\Traits;

use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait MiniAppHelpers
{
    protected function issueOnboardingToken(int $userId): string
    {
        $token = Str::random(48);
        Cache::put('tg_onb_token:' . $token, ['user_id' => $userId], now()->addMinutes(20));
        return $token;
    }

    private function resolveMiniAppRole(Request $request, ?User $user): string
    {
        if (!$user) {
            return 'student';
        }

        if ($user->role === 'teacher') {
            return 'teacher';
        }

        if ($user->role === 'admin') {
            $viewAsRole = $request->hasSession() ? $request->session()->get('view_as_role') : null;
            if (in_array($viewAsRole, ['student', 'teacher'], true)) {
                return $viewAsRole;
            }
        }

        return 'student';
    }

    private function variantModeLabel(?OgeVariant $variant): string
    {
        if (!$variant) return 'Вариант ОГЭ';

        return match ($variant->mode) {
            OgeVariant::MODE_MINI_ALGEBRA => 'Мини-ОГЭ — алгебра',
            OgeVariant::MODE_MINI_GEOMETRY => 'Мини-ОГЭ — геометрия',
            OgeVariant::MODE_MINI_MIXED => 'Мини-ОГЭ — смешанный',
            OgeVariant::MODE_MINI_PART2 => 'Мини-ОГЭ — 2 часть',
            OgeVariant::MODE_FULL_WITH_PART2 => 'Полный вариант (1+2 часть)',
            OgeVariant::MODE_FULL => 'Полный вариант',
            default => $variant->title ?: 'Вариант ОГЭ',
        };
    }

    protected function modeName(string $mode): string
    {
        return match ($mode) {
            'geometry' => 'Геометрия',
            'algebra' => 'Алгебра',
            'mixed' => 'Смешанное',
            default => $mode,
        };
    }
}
