<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('practice_game_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 64);
            $table->unsignedInteger('score')->default(0);
            $table->string('end_reason', 16)->nullable();
            $table->json('current_question')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['slug', 'ended_at', 'score'], 'practice_runs_leaderboard_idx');
            $table->index(['user_id', 'slug', 'score'], 'practice_runs_user_best_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_game_runs');
    }
};
