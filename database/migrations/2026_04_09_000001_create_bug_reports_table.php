<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 500);
            $table->string('route_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('user_agent');
            $table->json('screen_info')->nullable();   // width, height, dpr, pwa, online, connection
            $table->json('js_errors')->nullable();     // from window.onerror
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
