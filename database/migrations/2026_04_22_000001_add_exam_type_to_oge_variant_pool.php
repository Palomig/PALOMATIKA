<?php

use App\Models\OgeVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oge_variant_pool', function (Blueprint $table) {
            $table->enum('exam_type', [
                OgeVariant::EXAM_OGE,
                OgeVariant::EXAM_VPR5,
                OgeVariant::EXAM_VPR6,
                OgeVariant::EXAM_VPR7,
                OgeVariant::EXAM_VPR8,
                OgeVariant::EXAM_EGE,
            ])->default(OgeVariant::EXAM_OGE)->after('variant_id');
        });

        DB::table('oge_variant_pool as pool')
            ->join('oge_variants as variants', 'variants.id', '=', 'pool.variant_id')
            ->update([
                'pool.exam_type' => DB::raw('variants.exam_type'),
            ]);

        Schema::table('oge_variant_pool', function (Blueprint $table) {
            $table->dropUnique(['task_fingerprint']);
            $table->unique(['exam_type', 'task_fingerprint'], 'oge_variant_pool_exam_fingerprint_unique');
            $table->index(['exam_type', 'type', 'status'], 'oge_variant_pool_exam_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('oge_variant_pool', function (Blueprint $table) {
            $table->dropUnique('oge_variant_pool_exam_fingerprint_unique');
            $table->dropIndex('oge_variant_pool_exam_type_status_index');
            $table->unique('task_fingerprint');
            $table->dropColumn('exam_type');
        });
    }
};
