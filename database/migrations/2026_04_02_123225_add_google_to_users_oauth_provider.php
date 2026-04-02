<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN oauth_provider ENUM('telegram','vk','yandex','google') NULL");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('users')
            ->where('oauth_provider', 'google')
            ->update([
                'oauth_provider' => null,
                'oauth_id' => null,
            ]);

        DB::statement("ALTER TABLE users MODIFY COLUMN oauth_provider ENUM('telegram','vk','yandex') NULL");
    }
};
