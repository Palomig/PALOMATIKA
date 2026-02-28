<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oge_variant_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('oge_variants')->cascadeOnDelete();
            $table->enum('type', ['full', 'geometry', 'algebra', 'mixed']);
            $table->enum('status', ['active', 'deactivated'])->default('active');
            $table->string('task_fingerprint', 64)->unique();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'status']);
            $table->index('variant_id');
        });

        Schema::create('oge_variant_pool_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_id')->constrained('oge_variant_pool')->cascadeOnDelete();
            $table->string('topic_id', 2);
            $table->unsignedInteger('block_number');
            $table->unsignedInteger('zadanie_number');
            $table->unsignedInteger('task_id');
            $table->unsignedTinyInteger('sort_order');

            $table->index('pool_id');
            $table->index(['topic_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oge_variant_pool_tasks');
        Schema::dropIfExists('oge_variant_pool');
    }
};
