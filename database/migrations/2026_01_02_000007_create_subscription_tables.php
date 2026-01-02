<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // История подписок
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('plan', ['start', 'standard', 'premium']);
            $table->enum('plan_period', ['monthly', 'until_oge']);
            $table->boolean('has_ai_addon')->default(false);

            // Суммы
            $table->integer('amount'); // в копейках
            $table->integer('teacher_commission')->default(0); // комиссия учителю

            // Реферал
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Даты
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');

            // Статус
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');
            $table->timestamp('cancelled_at')->nullable();

            // Платёжка
            $table->string('payment_provider', 50)->default('robokassa');
            $table->string('payment_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'status']);
        });

        // Выплаты учителям
        Schema::create('teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();

            $table->integer('amount'); // в копейках

            // Статус
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            // Детали
            $table->string('payment_method', 50)->nullable();
            $table->json('payment_details')->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
        });

        // Детализация выплаты (какие подписки включены)
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained('teacher_payouts')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();

            $table->integer('amount'); // комиссия с этой подписки
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('teacher_payouts');
        Schema::dropIfExists('subscriptions');
    }
};
