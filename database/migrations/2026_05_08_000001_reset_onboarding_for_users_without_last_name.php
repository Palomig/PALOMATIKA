<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик #44 follow-up: чистим хвост от #17.
 *
 * Изначальная миграция 2026_05_07_200000 ресетила onboarding_completed_at
 * для юзеров без last_name, но middleware EnsurePwaOnboardingComplete
 * имел auto-heal по name+grade+school+city (без проверки last_name) —
 * и при следующем заходе чинил их обратно. Сегодня пофиксили middleware
 * (теперь auto-heal требует first_name + last_name), осталось ещё раз
 * сбросить тех, кого middleware успел auto-heal'ить.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'last_name')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('onboarding_completed_at')
            ->where(function ($q) {
                $q->whereNull('last_name')->orWhere('last_name', '');
            })
            ->update(['onboarding_completed_at' => null]);
    }

    public function down(): void
    {
        // No-op
    }
};
