<?php

namespace App\Console\Commands;

use App\Services\PdfParserService;
use Illuminate\Console\Command;

/**
 * Extract PDF files from git history
 *
 * Usage:
 *   php artisan pdf:extract-git --list                    # List available PDFs in git
 *   php artisan pdf:extract-git --task=07                 # Extract task 07 PDF
 *   php artisan pdf:extract-git --commit=b8c1426 --all   # Extract all PDFs from commit
 */
class ExtractPdfFromGitCommand extends Command
{
    protected $signature = 'pdf:extract-git
                            {--list : List available PDF files in git history}
                            {--task= : Task number to extract (e.g., 07)}
                            {--commit=b8c1426 : Git commit hash where PDFs are stored}
                            {--all : Extract all PDFs}';

    protected $description = 'Extract PDF task files from git history';

    /**
     * Known PDF files in git history
     */
    protected array $knownPdfs = [
        '06' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №06 (трен) v2.pdf',
        '07' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №07 (трен).pdf',
        '08' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №08 (трен).pdf',
        '09' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №09 (трен).pdf',
        '10' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №10 (трен).pdf',
        '11' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №11 (трен).pdf',
        '12' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №12 (трен).pdf',
        '13' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №13 (трен).pdf',
        '14' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №14 (трен).pdf',
        '15' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №15 (трен).pdf',
        '16' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №16 (трен).pdf',
        '17' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №17 (трен).pdf',
        '18' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №18 (трен).pdf',
        '19' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №19 (трен).pdf',
        '20' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №20 (трен).pdf',
        '21' => 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №21 (трен).pdf',
    ];

    protected PdfParserService $parser;

    public function __construct(PdfParserService $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    public function handle(): int
    {
        $commit = $this->option('commit');

        // List mode
        if ($this->option('list')) {
            return $this->listPdfs($commit);
        }

        // Extract single task
        if ($task = $this->option('task')) {
            return $this->extractTask($task, $commit);
        }

        // Extract all
        if ($this->option('all')) {
            return $this->extractAll($commit);
        }

        $this->error('Please specify --list, --task=XX, or --all');
        return 1;
    }

    protected function listPdfs(string $commit): int
    {
        $this->info("Known PDF files (commit: {$commit}):");
        $this->newLine();

        $this->table(
            ['Task', 'Git Path', 'Status'],
            array_map(function ($task, $path) {
                $outputFile = "task_{$task}.pdf";
                $exists = file_exists($this->parser->getStoragePath() . '/' . $outputFile);
                return [$task, $path, $exists ? 'Extracted' : 'Not extracted'];
            }, array_keys($this->knownPdfs), $this->knownPdfs)
        );

        $this->newLine();
        $this->line('To extract: php artisan pdf:extract-git --task=XX');
        $this->line('To extract all: php artisan pdf:extract-git --all');

        // Also try to list from git
        $this->newLine();
        $this->info("Scanning git history for more PDFs...");

        $output = shell_exec("git show {$commit} --name-only --oneline 2>/dev/null | grep -i '\\.pdf$'");
        if ($output) {
            $this->line("Found in git:");
            foreach (explode("\n", trim($output)) as $file) {
                if (!empty($file)) {
                    $this->line("  - {$file}");
                }
            }
        }

        return 0;
    }

    protected function extractTask(string $task, string $commit): int
    {
        // Normalize task number
        $task = str_pad($task, 2, '0', STR_PAD_LEFT);

        if (!isset($this->knownPdfs[$task])) {
            $this->error("Unknown task: {$task}");
            $this->line('Known tasks: ' . implode(', ', array_keys($this->knownPdfs)));
            return 1;
        }

        $gitPath = $this->knownPdfs[$task];
        $outputFile = "task_{$task}.pdf";

        $this->info("Extracting task {$task}...");
        $this->line("  From: {$gitPath}");
        $this->line("  To: storage/app/pdf/{$outputFile}");

        $success = $this->parser->extractFromGit($commit, $gitPath, $outputFile);

        if ($success) {
            $this->info("Successfully extracted!");

            $fullPath = $this->parser->getStoragePath() . '/' . $outputFile;
            $size = filesize($fullPath);
            $this->line("  Size: " . number_format($size / 1024, 1) . " KB");

            $this->newLine();
            $this->line("Next steps:");
            $this->line("  php artisan pdf:parse {$outputFile} --analyze");
            $this->line("  php artisan pdf:parse {$outputFile} --topic={$task}");
        } else {
            $this->error("Failed to extract PDF");
            return 1;
        }

        return 0;
    }

    protected function extractAll(string $commit): int
    {
        $this->info("Extracting all PDFs from commit {$commit}...");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($this->knownPdfs as $task => $gitPath) {
            $outputFile = "task_{$task}.pdf";

            $this->line("Extracting task {$task}...");

            if ($this->parser->extractFromGit($commit, $gitPath, $outputFile)) {
                $this->info("  OK");
                $success++;
            } else {
                $this->error("  FAILED");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done! Extracted: {$success}, Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
