<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_assistant_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $t->enum('role', ['teacher', 'assistant']);
            $t->text('content');
            $t->timestamp('created_at')->useCurrent();
            $t->index('lesson_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_assistant_messages');
    }
};
