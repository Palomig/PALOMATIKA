<?php

namespace App\Console\Commands;

use App\Services\GeometrySvgRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Bake SVG strings into EGE JSON file (one-time generation)
 *
 * This command reads geometry data from ege/topic_XX_geometry.json,
 * renders SVG for each task using GeometrySvgRenderer,
 * and saves the result to ege/topic_XX.json with ready-to-use SVG strings.
 *
 * Usage:
 *   php artisan svg:bake-ege 01
 *   php artisan svg:bake-ege 01 --dry-run
 */
class BakeEgeSvgToJson extends Command
{
    protected $signature = 'svg:bake-ege
                            {topic : Topic ID (01, 02, ...)}
                            {--dry-run : Show what would be done without saving}';

    protected $description = 'Bake SVG strings into EGE JSON file (one-time generation)';

    public function handle(): int
    {
        $topicId = str_pad($this->argument('topic'), 2, '0', STR_PAD_LEFT);
        $dryRun = $this->option('dry-run');

        $basePath = storage_path('app/tasks/ege');
        $geometryPath = "{$basePath}/topic_{$topicId}_geometry.json";

        if (!File::exists($geometryPath)) {
            $this->error("Geometry file not found: {$geometryPath}");
            return Command::FAILURE;
        }

        $this->info("Reading EGE geometry data from: ege/topic_{$topicId}_geometry.json");

        $data = json_decode(File::get($geometryPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("JSON parse error: " . json_last_error_msg());
            return Command::FAILURE;
        }

        $renderer = new GeometrySvgRenderer();
        $count = 0;
        $errors = 0;

        // Process all blocks and zadaniya
        foreach ($data['blocks'] as $blockIndex => &$block) {
            $this->info("Block {$block['number']}: {$block['title']}");

            foreach ($block['zadaniya'] as $zadanieIndex => &$zadanie) {
                // Skip if no svg_type or geometry
                if (!isset($zadanie['svg_type']) || !isset($zadanie['geometry'])) {
                    continue;
                }

                $svgType = $zadanie['svg_type'];
                $geometry = $zadanie['geometry'];

                $this->line("  Zadanie {$zadanie['number']}: {$svgType}");

                // Check if renderer supports this type
                if (!$renderer->supports($svgType)) {
                    $this->warn("    ⚠️  Unsupported svg_type: {$svgType}");
                    continue;
                }

                // Render SVG for each task
                foreach ($zadanie['tasks'] as $taskIndex => &$task) {
                    try {
                        $params = $task['params'] ?? [];

                        $svg = $renderer->render($svgType, $geometry, $params);

                        // Store the rendered SVG directly in task
                        $task['svg'] = $svg;

                        $this->line("    ✓ Task {$task['id']}: {$svgType}");
                        $count++;
                    } catch (\Exception $e) {
                        $this->error("    ✗ Task {$task['id']}: " . $e->getMessage());
                        $errors++;
                    }
                }
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("DRY RUN: Would generate {$count} SVGs ({$errors} errors)");
            $this->info("No files were modified.");
            return Command::SUCCESS;
        }

        // Update metadata
        $data['exported_at'] = now()->toIso8601String();
        $data['svg_baked'] = true;

        // Save to main JSON file (overwrite)
        $outputPath = "{$basePath}/topic_{$topicId}.json";

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (File::put($outputPath, $json) === false) {
            $this->error("Failed to write to: {$outputPath}");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Done! Generated {$count} SVGs ({$errors} errors)");
        $this->info("   Saved to: {$outputPath}");
        $this->newLine();
        $this->comment("Next steps:");
        $this->comment("  1. Verify SVGs look correct on /ege/topics/{$topicId}");
        $this->comment("  2. Clear cache: php artisan cache:clear");

        return Command::SUCCESS;
    }
}
