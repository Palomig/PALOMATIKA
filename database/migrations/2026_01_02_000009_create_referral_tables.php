<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Клики по реферальным ссылкам
        Schema::create('referral_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('referral_code', 50);

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Конверсия
            $table->foreignId('registered_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('clicked_at')->useCurrent();

            $table->index('referral_code');
            $table->index('clicked_at');
        });

        // Связь учитель-ученик
        Schema::create('teacher_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Как попал
            $table->enum('source', ['referral', 'manual', 'homework_invite'])->default('referral');

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['teacher_id', 'student_id'], 'unique_pair');
        });

        // Миграция для sessions (нужна для session driver = database)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('teacher_students');
        Schema::dropIfExists('referral_clicks');
    }
};
