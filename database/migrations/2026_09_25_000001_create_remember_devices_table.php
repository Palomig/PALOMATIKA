<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Запомнить меня» по устройствам: у каждого браузера/PWA свой токен.
     * Раньше токен был один на пользователя (users.remember_token), и выход
     * на одном устройстве разлогинивал все остальные.
     */
    public function up(): void
    {
        Schema::create('remember_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip', 45)->nullable();
            // dateTime, не timestamp: на проде explicit_defaults_for_timestamp=OFF
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index(['user_id', 'last_used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remember_devices');
    }
};
