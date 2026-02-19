<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jarvis_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('source_content');
            $table->string('status', 24)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('owner_teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jarvis_materials');
    }
};
