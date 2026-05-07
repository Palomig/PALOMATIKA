<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RedesignBackupPuzzlesCommand extends Command
{
    protected $signature = 'redesign:backup-puzzles {--path=backups/redesign-44}';

    protected $description = 'Dump legacy puzzle/task tables to JSON before drop. Run before drop migration. Epic #44 / task #46.';

    private const TABLES = [
        'topics',
        'puzzle_templates',
        'tasks',
        'task_skills',
        'task_steps',
        'step_blocks',
        'attempts',
        'attempt_steps',
        'step_block_selections',
        'homework_tasks',
    ];

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $disk = Storage::disk('local');
        $disk->makeDirectory($path);

        $manifest = ['created_at' => now()->toIso8601String(), 'tables' => []];
        $totalRows = 0;

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("- skip {$table} (not present)");
                $manifest['tables'][$table] = ['present' => false];
                continue;
            }

            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $count = count($rows);
            $totalRows += $count;

            $disk->put("{$path}/{$table}.json", json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $manifest['tables'][$table] = ['present' => true, 'rows' => $count];

            $this->info("- {$table}: {$count} rows → {$path}/{$table}.json");
        }

        $disk->put("{$path}/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info("Backup complete: {$totalRows} rows across " . count(self::TABLES) . " tables. Manifest: {$path}/manifest.json");

        return self::SUCCESS;
    }
}
