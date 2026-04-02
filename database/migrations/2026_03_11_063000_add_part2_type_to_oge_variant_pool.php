<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE oge_variant_pool MODIFY COLUMN type ENUM('full','geometry','algebra','mixed','part2','full_with_part2') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE oge_variant_pool MODIFY COLUMN type ENUM('full','geometry','algebra','mixed') NOT NULL");
    }
};
