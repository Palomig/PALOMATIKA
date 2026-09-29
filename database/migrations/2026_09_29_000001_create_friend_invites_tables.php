<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Позови друга»: ученик 8–11 класса приводит друга на бесплатное первое
     * занятие в группе. Кто привёл — со слов новичка, фиксирует преподаватель.
     * Выплата делится поровну между всеми, кого назвал новичок.
     *
     * Отдельно от users.referred_by_user_id: та связь привязывает учеников
     * к учителю по ссылке-приглашению, смешивать нельзя.
     *
     * dateTime, не timestamp: на проде explicit_defaults_for_timestamp=OFF.
     */
    public function up(): void
    {
        Schema::create('friend_invites', function (Blueprint $table) {
            $table->id();
            $table->string('invitee_name', 100);
            $table->unsignedTinyInteger('invitee_grade')->nullable();
            $table->date('first_lesson_on');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            // Оплатил занятия и пришёл на второе — отмечает преподаватель.
            $table->dateTime('qualified_at')->nullable();
            $table->foreignId('qualified_by')->nullable()->constrained('users')->nullOnDelete();
            // Не остался — выплаты по нему не будет.
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('first_lesson_on');
        });

        Schema::create('friend_invite_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invite_id')->constrained('friend_invites')->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();

            $table->unique(['invite_id', 'referrer_id']);
            $table->index('referrer_id');
        });

        // Бонус +3000 ₽ за каждые 6000 ₽ заработанного (= каждый третий друг).
        Schema::create('friend_invite_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('threshold');
            $table->unsignedInteger('amount');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();

            $table->unique(['referrer_id', 'threshold']);
        });

        // Отметки ученика «кого позвал» — заметка с датой, на деньги не влияет.
        Schema::create('friend_invite_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->dateTime('created_at')->nullable();

            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('invite_board_hidden')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('invite_board_hidden');
        });
        Schema::dropIfExists('friend_invite_notes');
        Schema::dropIfExists('friend_invite_bonuses');
        Schema::dropIfExists('friend_invite_credits');
        Schema::dropIfExists('friend_invites');
    }
};
