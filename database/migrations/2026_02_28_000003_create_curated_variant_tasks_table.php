<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curated_variant_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('oge_variants')->cascadeOnDelete();
            $table->unsignedTinyInteger('task_number');
            $table->string('topic_id', 2);
            $table->unsignedInteger('block_number');
            $table->unsignedInteger('zadanie_number');
            $table->unsignedInteger('task_index');
            $table->unsignedTinyInteger('sort_order');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['variant_id', 'sort_order'], 'curated_unique_sort');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curated_variant_tasks');
    }
};
