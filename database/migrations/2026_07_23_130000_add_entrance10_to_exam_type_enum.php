<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE oge_variants MODIFY exam_type ENUM('oge','vpr_5','vpr_6','vpr_7','vpr_8','ege','entrance10') NOT NULL DEFAULT 'oge'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE oge_variants MODIFY exam_type ENUM('oge','vpr_5','vpr_6','vpr_7','vpr_8','ege') NOT NULL DEFAULT 'oge'");
    }
};
