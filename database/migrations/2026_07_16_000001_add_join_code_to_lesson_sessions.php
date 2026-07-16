<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->char('join_code', 4)->nullable()->after('invite_token')->index();
        });
        // source: + 'code' (mysql only; sqlite-ветка пропускается — см. 2026_04_23_000001)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lesson_session_participants MODIFY source ENUM('schedule','invite','code') NOT NULL DEFAULT 'code'");
        }
    }

    public function down(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->dropColumn('join_code');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lesson_session_participants MODIFY source ENUM('schedule','invite') NOT NULL DEFAULT 'schedule'");
        }
    }
};
