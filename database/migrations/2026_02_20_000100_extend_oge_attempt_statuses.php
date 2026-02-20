<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE oge_attempts MODIFY COLUMN status ENUM('active', 'submitted', 'scored', 'error') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('oge_attempts')
            ->whereIn('status', ['scored', 'error'])
            ->update(['status' => 'submitted']);

        DB::statement("ALTER TABLE oge_attempts MODIFY COLUMN status ENUM('active', 'submitted') NOT NULL DEFAULT 'active'");
    }
};
