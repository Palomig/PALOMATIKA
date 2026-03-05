<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('topic_id', 4);
            $table->string('task_key', 120);
            $table->string('status', 16)->default('draft');
            $table->timestamps();

            $table->unique(['topic_id', 'task_key']);
            $table->index(['topic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
