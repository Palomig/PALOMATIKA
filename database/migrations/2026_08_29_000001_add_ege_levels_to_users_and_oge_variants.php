<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ege_level', 8)->nullable()->after('grade_letter');
        });

        Schema::table('oge_variants', function (Blueprint $table) {
            $table->string('level', 8)->nullable()->after('exam_type')->index();
        });

        DB::table('oge_variants')
            ->where('exam_type', 'ege')
            ->select(['id', 'config_json'])
            ->orderBy('id')
            ->chunkById(100, function ($variants): void {
                foreach ($variants as $variant) {
                    $config = json_decode((string) $variant->config_json, true) ?: [];
                    $level = ($config['level'] ?? null) === 'base' ? 'base' : 'prof';
                    DB::table('oge_variants')->where('id', $variant->id)->update(['level' => $level]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ege_level');
        });
    }
};
