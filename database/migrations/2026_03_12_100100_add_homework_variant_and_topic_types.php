<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM('specific_tasks','topic_random','weak_skills','full_variant','topic_practice') NOT NULL");
        }

        Schema::table('homeworks', function (Blueprint $table) {
            $table->string('variant_hash', 32)->nullable()->after('tasks_count');
            $table->tinyInteger('topic_number')->unsigned()->nullable()->after('variant_hash');
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropColumn(['variant_hash', 'topic_number']);
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM('specific_tasks','topic_random','weak_skills') NOT NULL");
    }
};
