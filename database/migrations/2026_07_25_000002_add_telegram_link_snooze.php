<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Напомнить позже» для экрана привязки телеграма: пока стоит дата в будущем,
 * ученика на экран не уводим. Жёсткого блока нет — привязка остаётся просьбой.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('telegram_link_snoozed_until')->nullable()->after('telegram_blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_link_snoozed_until');
        });
    }
};
