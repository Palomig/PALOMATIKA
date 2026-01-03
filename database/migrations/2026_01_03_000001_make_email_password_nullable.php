<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make email nullable for OAuth users (Telegram doesn't provide email)
            $table->string('email')->nullable()->change();

            // Make password nullable for OAuth users
            $table->string('password')->nullable()->change();
        });

        // Remove unique constraint on email to allow multiple null values
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique('email'); // Re-add unique but it will allow multiple nulls
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
