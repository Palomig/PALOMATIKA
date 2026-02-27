<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade_num')->nullable()->after('grade');
            $table->string('grade_letter', 2)->nullable()->after('grade_num');
            $table->string('school_number', 20)->nullable()->after('grade_letter');
            $table->string('city', 80)->nullable()->default('Чехов')->after('school_number');
            $table->timestamp('onboarding_completed_at')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'grade_num',
                'grade_letter',
                'school_number',
                'city',
                'onboarding_completed_at',
            ]);
        });
    }
};
