<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_session_participants', function (Blueprint $table) {
            $table->timestamp('locked_until')->nullable()->after('joined_at')->index();
            $table->timestamp('released_at')->nullable()->after('locked_until');
            $table->foreignId('released_by')->nullable()->after('released_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_session_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('released_by');
            $table->dropColumn(['released_at', 'locked_until']);
        });
    }
};
