<?php

namespace App\Console\Commands;

use App\Services\GeometrySvgRenderer;
use App\Services\GraphSvgRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Bake SVG strings into JSON file (one-time generation)
 *
 * This command reads geometry data from topic_XX_geometry.json,
 * renders SVG for each task using GeometrySvgRenderer or GraphSvgRenderer,
 * and saves the result to topic_XX.json with ready-to-use SVG strings.
 *
 * Supported topics:
 * - 11: Графики функций (GraphSvgRenderer)
 * - 15, 16, 17: Геометрия (GeometrySvgRenderer)
 *
 * After running this command, the geometry JSON files can be deleted
 * as the SVG is now stored directly in the main JSON.
 */
class BakeSvgToJson extends Command
{
    protected $signature = 'svg:bake
                            {topic : Topic ID (11, 15, 16, 17)}
                            {--dry-run : Show what would be done without saving}';

    protected $description = 'Bake SVG strings into JSON file (one-time generation)';

    public function handle(): int
    {
        $topicId = str_pad($this->argument('topic'), 2, '0', STR_PAD_LEFT);
        $dryRun = $this->option('dry-run');

        // Topic 11 (graphs) uses different approach - reads from main JSON
        if ($topicId === '11') {
            return $this->handleTopic11($dryRun);
        }

        $geometryPath = storage_path("app/tasks/topic_{$topicId}_geometry.json");

        if (!File::exists($geometryPath)) {
            $this->error("Geometry file not found: {$geometryPath}");
            return Command::FAILURE;
        }

        $this->info("Reading geometry data from: topic_{$topicId}_geometry.json");

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
            foreach ($block['zadaniya'] as $zadanieIndex => &$zadanie) {
                // Skip if no svg_type or geometry
                if (!isset($zadanie['svg_type']) || !isset($zadanie['geometry'])) {
                    continue;
                }

                $svgType = $zadanie['svg_type'];
                $geometry = $zadanie['geometry'];

                // Check if renderer supports this type
                if (!$renderer->supports($svgType)) {
                    $this->warn("  ⚠️  Unsupported svg_type: {$svgType}");
                    continue;
                }

                // Render SVG for each task
                foreach ($zadanie['tasks'] as $taskIndex => &$task) {
                    try {
                        $params = $task['params'] ?? [];

                        $svg = $renderer->render($svgType, $geometry, $params);

                        // Store the rendered SVG directly in task
                        $task['svg'] = $svg;

                        $this->line("  ✓ Task {$task['id']}: {$svgType}");
                        $count++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ Task {$task['id']}: " . $e->getMessage());
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
        $outputPath = storage_path("app/tasks/topic_{$topicId}.json");

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
        $this->comment("  1. Verify SVGs look correct: php artisan tinker");
        $this->comment("     > json_decode(file_get_contents(storage_path('app/tasks/topic_{$topicId}.json')), true)['blocks'][0]['zadaniya'][0]['tasks'][0]['svg']");
        $this->comment("  2. Delete geometry file: rm storage/app/tasks/topic_{$topicId}_geometry.json");
        $this->comment("  3. Clear cache: php artisan cache:clear");

        return Command::SUCCESS;
    }

    /**
     * Handle topic 11 (graphs) - generates SVG from formula in options
     */
    private function handleTopic11(bool $dryRun): int
    {
        $inputPath = storage_path("app/tasks/topic_11.json");

        if (!File::exists($inputPath)) {
            $this->error("Topic file not found: {$inputPath}");
            return Command::FAILURE;
        }

        $this->info("Reading topic 11 data from: topic_11.json");

        $data = json_decode(File::get($inputPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("JSON parse error: " . json_last_error_msg());
            return Command::FAILURE;
        }

        $renderer = new GraphSvgRenderer();
        $count = 0;
        $errors = 0;

        // Process all blocks and zadaniya
        foreach ($data['blocks'] as $blockIndex => &$block) {
            $this->info("Block {$block['number']}: {$block['title']}");

            foreach ($block['zadaniya'] as $zadanieIndex => &$zadanie) {
                $type = $zadanie['type'] ?? '';

                // Only process matching types and statements
                if (!in_array($type, ['matching', 'matching_signs', 'matching_4', 'statements', 'graph_statements'])) {
                    continue;
                }

                $this->line("  Zadanie {$zadanie['number']}: {$type}");

                foreach ($zadanie['tasks'] as $taskIndex => &$task) {
                    try {
                        // Get formula from task or first option
                        $formula = $task['formula'] ?? null;

                        // If no explicit formula, use first option (usually correct answer)
                        if (!$formula && isset($task['options'][0])) {
                            $formula = $task['options'][0];
                        }

                        if (!$formula) {
                            $this->warn("    ⚠️  Task {$task['id']}: No formula found");
                            continue;
                        }

                        // Clean formula for display
                        $cleanFormula = preg_replace('/\\\\frac\{(-?\d+)\}\{(\d+)\}/', '$1/$2', $formula);
                        $cleanFormula = str_replace(['\\', ' '], '', $cleanFormula);

                        // Check if renderer can handle this formula
                        if (!$renderer->supports($formula)) {
                            $this->warn("    ⚠️  Task {$task['id']}: Unsupported formula: {$cleanFormula}");
                            continue;
                        }

                        // Render SVG
                        $svg = $renderer->render($formula);

                        // Store the rendered SVG
                        $task['svg'] = $svg;

                        $this->line("    ✓ Task {$task['id']}: {$cleanFormula}");
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

        // Save to main JSON file
        $outputPath = storage_path("app/tasks/topic_11.json");

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (File::put($outputPath, $json) === false) {
            $this->error("Failed to write to: {$outputPath}");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("Done! Generated {$count} SVGs ({$errors} errors)");
        $this->info("   Saved to: {$outputPath}");
        $this->newLine();
        $this->comment("Next steps:");
        $this->comment("  1. Clear cache: php artisan cache:clear");

        return Command::SUCCESS;
    }
}
