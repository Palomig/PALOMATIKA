<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->string('mode', 32)->nullable()->after('config_json');
            $table->boolean('is_curated')->default(false)->after('mode');

            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::table('oge_variants', function (Blueprint $table) {
            $table->dropIndex(['mode']);
            $table->dropColumn(['mode', 'is_curated']);
        });
    }
};
