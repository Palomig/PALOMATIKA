<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('oge_attempts', function (Blueprint $table) {
            $table->json('frozen_answers_json')->nullable()->after('attempt_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::table('oge_attempts', function (Blueprint $table) {
            $table->dropColumn('frozen_answers_json');
        });
    }
};
