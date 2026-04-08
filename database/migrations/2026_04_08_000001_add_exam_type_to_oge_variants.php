<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->enum('exam_type', ['oge', 'vpr_5', 'vpr_6', 'vpr_7', 'vpr_8', 'ege'])
                  ->default('oge')
                  ->after('hash');
            $table->index('exam_type');
        });
    }

    public function down(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->dropIndex(['exam_type']);
            $table->dropColumn('exam_type');
        });
    }
};
