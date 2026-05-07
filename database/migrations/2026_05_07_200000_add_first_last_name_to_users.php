<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик #44 follow-up: задача #17 (имя + фамилия обязательны).
 *
 * Добавляем first_name/last_name. Бэкфилл из существующего `name`:
 * - если `name` содержит пробел — split по первому пробелу
 * - если одно слово — кладём всё в first_name, last_name остаётся NULL
 *
 * Для пользователей с пустой фамилией ресетим onboarding_completed_at,
 * чтобы PWA принудительно прогнала их через онбординг и заставила ввести
 * настоящие имя+фамилию (вместо никнеймов).
 *
 * Колонка `name` остаётся в БД и синхронизируется приложением для
 * обратной совместимости с существующим кодом.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
        });

        // Backfill из name.
        DB::table('users')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $name = trim((string) $user->name);
                    if ($name === '') {
                        continue;
                    }

                    $parts = preg_split('/\s+/u', $name) ?: [$name];
                    $first = $parts[0];
                    $last = count($parts) > 1
                        ? trim(implode(' ', array_slice($parts, 1)))
                        : null;

                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $first,
                        'last_name'  => $last !== '' ? $last : null,
                    ]);
                }
            });

        // Принудительный re-onboarding для пользователей без фамилии.
        DB::table('users')
            ->whereNotNull('onboarding_completed_at')
            ->where(function ($q) {
                $q->whereNull('last_name')->orWhere('last_name', '');
            })
            ->update(['onboarding_completed_at' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
        });
    }
};
