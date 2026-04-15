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

        $isVpr = in_array($variant->exam_type, [
            OgeVariant::EXAM_VPR5,
            OgeVariant::EXAM_VPR6,
            OgeVariant::EXAM_VPR7,
            OgeVariant::EXAM_VPR8,
        ], true);

        return match ($variant->mode) {
            OgeVariant::MODE_MINI_ALGEBRA => $isVpr ? 'Мини-ВПР — алгебра' : 'Мини-ОГЭ — алгебра',
            OgeVariant::MODE_MINI_GEOMETRY => $isVpr ? 'Мини-ВПР — геометрия' : 'Мини-ОГЭ — геометрия',
            OgeVariant::MODE_MINI_MIXED => $isVpr ? 'Мини-ВПР — смешанный' : 'Мини-ОГЭ — смешанный',
            OgeVariant::MODE_MINI_PART2 => $isVpr ? 'Мини-ВПР — 2 часть' : 'Мини-ОГЭ — 2 часть',
            OgeVariant::MODE_FULL_WITH_PART2 => $isVpr ? 'Полный вариант ВПР (1+2 часть)' : 'Полный вариант (1+2 часть)',
            OgeVariant::MODE_FULL => $isVpr ? 'Полный вариант ВПР' : 'Полный вариант',
            default => $variant->title ?: ($isVpr ? 'Вариант ВПР' : 'Вариант ОГЭ'),
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
