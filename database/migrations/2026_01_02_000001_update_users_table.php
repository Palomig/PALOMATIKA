<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // OAuth
            $table->enum('oauth_provider', ['telegram', 'vk', 'yandex'])->nullable()->after('remember_token');
            $table->string('oauth_id')->nullable()->after('oauth_provider');

            // Профиль
            $table->string('avatar')->nullable()->after('name');

            // Роль
            $table->enum('role', ['student', 'teacher', 'admin'])->default('student')->after('avatar');

            // Для учеников
            $table->tinyInteger('grade')->nullable()->after('role'); // 8, 9
            $table->string('school')->nullable()->after('grade');

            // Для учителей (партнёров)
            $table->string('referral_code', 50)->unique()->nullable()->after('school');
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('partner_commission_percent')->default(30);
            $table->enum('partner_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->timestamp('partner_approved_at')->nullable();

            // Подписка
            $table->enum('subscription_plan', ['free', 'start', 'standard', 'premium'])->default('free');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('has_ai_addon')->default(false);
            $table->timestamp('trial_ends_at')->nullable();

            // Активность
            $table->timestamp('last_active_at')->nullable();
            $table->string('timezone', 50)->default('Europe/Moscow');

            // Индексы
            $table->index('referral_code');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropIndex(['referral_code']);
            $table->dropIndex(['role']);

            $table->dropColumn([
                'oauth_provider', 'oauth_id', 'avatar', 'role',
                'grade', 'school', 'referral_code', 'referred_by_user_id',
                'partner_commission_percent', 'partner_status', 'partner_approved_at',
                'subscription_plan', 'subscription_ends_at', 'has_ai_addon', 'trial_ends_at',
                'last_active_at', 'timezone'
            ]);
        });
    }
};
