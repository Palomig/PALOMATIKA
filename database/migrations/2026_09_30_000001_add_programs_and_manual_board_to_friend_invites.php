<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Позови друга» делится на две акции:
     *  - cash (8–11 класс): 2000 ₽ наличными, +3000 ₽ за каждого третьего;
     *  - discount (6–7 класс): скидка 50% на следующий месяц и пригласившему,
     *    и приглашённому.
     * Доска зовущих теперь ведётся вручную супер-админом, своя у каждой акции.
     *
     * friend_invite_notes больше не используется (кнопку «Отметить, кого позвал»
     * убрали) — таблицу не трогаем, чтобы не терять записи.
     */
    public function up(): void
    {
        Schema::table('friend_invites', function (Blueprint $table) {
            $table->string('program', 10)->default('cash')->after('id');
            // Скидка новичку (акция discount) применена к его следующему месяцу.
            $table->dateTime('invitee_discount_applied_at')->nullable()->after('cancelled_at');
        });

        Schema::create('friend_board_entries', function (Blueprint $table) {
            $table->id();
            $table->string('program', 10);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('friends')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['program', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_board_entries');
        Schema::table('friend_invites', function (Blueprint $table) {
            $table->dropColumn(['program', 'invitee_discount_applied_at']);
        });
    }
};
