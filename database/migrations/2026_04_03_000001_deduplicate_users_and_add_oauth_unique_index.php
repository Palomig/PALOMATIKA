<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Deduplicate — for each (oauth_provider, oauth_id) keep the row with the smallest id,
        // then re-parent all FK references from duplicate rows to the canonical user, and delete duplicates.
        $duplicates = DB::select("
            SELECT oauth_provider, oauth_id, MIN(id) AS keep_id, GROUP_CONCAT(id ORDER BY id) AS all_ids
            FROM users
            WHERE oauth_provider IS NOT NULL AND oauth_id IS NOT NULL
            GROUP BY oauth_provider, oauth_id
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            $allIds = array_map('intval', explode(',', $dup->all_ids));
            $keepId = (int) $dup->keep_id;
            $removeIds = array_values(array_filter($allIds, fn($id) => $id !== $keepId));

            if (empty($removeIds)) {
                continue;
            }

            $removePlaceholders = implode(',', array_fill(0, count($removeIds), '?'));

            // Re-parent FK references in tables that reference users.id
            $fkTables = [
                'oge_attempts'         => 'user_id',
                'oge_variants'         => 'user_id',
                'homework_assignments' => 'teacher_id',
                'homework_submissions' => 'student_id',
                'audit_events'         => 'actor_id',
                'user_gifts'           => 'user_id',
            ];

            foreach ($fkTables as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::statement(
                        "UPDATE `{$table}` SET `{$column}` = ? WHERE `{$column}` IN ({$removePlaceholders})",
                        array_values(array_merge([$keepId], $removeIds))
                    );
                }
            }

            // Delete duplicate rows
            DB::statement(
                "DELETE FROM users WHERE id IN ({$removePlaceholders})",
                $removeIds
            );
        }

        // Step 2: Add UNIQUE index so duplicates can never happen again
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['oauth_provider', 'oauth_id'], 'users_oauth_provider_oauth_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_oauth_provider_oauth_id_unique');
        });
    }
};
