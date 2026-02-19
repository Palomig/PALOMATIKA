<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('title');
            $table->string('external_ref', 255)->nullable()->after('source');
            $table->string('created_via', 64)->nullable()->after('external_ref');

            $table->index('source');
            $table->unique(['owner_teacher_id', 'external_ref'], 'oge_variants_owner_external_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->dropUnique('oge_variants_owner_external_ref_unique');
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'external_ref', 'created_via']);
        });
    }
};
