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

        // Drop legacy strict uniqueness (blocked re-attempts forever).
        DB::statement('ALTER TABLE oge_attempts DROP INDEX oge_unique_variant_student');

        // Enforce uniqueness only for active attempts:
        // active_unique_key = 1 when status=active, else NULL.
        // Unique index allows multiple NULL rows, but only one active row per pair.
        DB::statement("ALTER TABLE oge_attempts ADD COLUMN active_unique_key TINYINT AS (CASE WHEN status = 'active' THEN 1 ELSE NULL END) STORED");
        DB::statement('ALTER TABLE oge_attempts ADD UNIQUE INDEX oge_unique_active_attempt (variant_id, student_id, active_unique_key)');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE oge_attempts DROP INDEX oge_unique_active_attempt');
        DB::statement('ALTER TABLE oge_attempts DROP COLUMN active_unique_key');
        DB::statement('ALTER TABLE oge_attempts ADD UNIQUE INDEX oge_unique_variant_student (variant_id, student_id)');
    }
};
