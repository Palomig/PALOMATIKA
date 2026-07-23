<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('homework_assignments', function (Blueprint $table) {
            $table->dateTime('notified_at')->nullable()->after('completed_at'); // уведомление о новом ДЗ
            $table->dateTime('reminded_at')->nullable()->after('notified_at');  // последнее напоминание о сроке
        });
        Schema::table('users', function (Blueprint $table) {
            $table->date('homework_popup_shown_on')->nullable()->after('last_active_at'); // анти-надоедание поп-апа
        });
    }

    public function down(): void
    {
        Schema::table('homework_assignments', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'reminded_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('homework_popup_shown_on');
        });
    }
};
