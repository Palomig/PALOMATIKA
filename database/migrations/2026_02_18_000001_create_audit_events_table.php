<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('event_type', 64);
            $table->string('category', 32);
            $table->string('severity', 16)->default('info');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->nullable();
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index('occurred_at');
            $table->index(['event_type', 'occurred_at']);
            $table->index(['category', 'occurred_at']);
            $table->index(['severity', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
