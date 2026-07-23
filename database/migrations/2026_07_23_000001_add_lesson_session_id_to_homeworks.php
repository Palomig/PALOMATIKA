<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            // Связь «ДЗ создано по итогам урока» — для плашки «уже отправлялось»
            $table->foreignId('lesson_session_id')->nullable()->after('teacher_id')
                ->constrained('lesson_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_session_id');
        });
    }
};
