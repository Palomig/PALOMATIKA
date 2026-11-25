<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_login_numbers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->char('phone', 11)->unique();
        });
        Schema::create('phone_login_codes', function (Blueprint $table) {
            $table->char('id', 48)->primary();
            $table->char('phone', 11);
            $table->unsignedBigInteger('actor');
            $table->unsignedBigInteger('owner');
            $table->char('digest', 64);
            $table->integer('attempts');
            $table->bigInteger('expires_at')->index();
        });
        Schema::create('phone_login_limits', function (Blueprint $table) {
            $table->char('bucket', 64)->primary();
            $table->bigInteger('period');
            $table->integer('hits');
        });
    }

    public function down(): void
    {
        foreach (['phone_login_codes', 'phone_login_limits', 'phone_login_numbers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
