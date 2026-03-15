<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('tg_premium_until')->nullable()->after('trial_ends_at');
            $table->boolean('tg_trial_used')->default(false)->after('tg_premium_until');
            $table->integer('star_balance')->default(0)->after('tg_trial_used');
        });

        Schema::create('star_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'referral_bonus', 'payout']);
            $table->integer('amount');
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('telegram_charge_id')->nullable();
            $table->enum('status', ['completed', 'pending'])->default('completed');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('star_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tg_premium_until', 'tg_trial_used', 'star_balance']);
        });
    }
};
