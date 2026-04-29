<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Post-deploy refresh command
 *
 * Clears all Laravel caches and regenerates SVGs for geometry topics
 * where the source file (*_geometry.json) is newer than the output file (*.json).
 *
 * Usage:
 *   php artisan deploy:refresh           # Full refresh with auto-bake
 *   php artisan deploy:refresh --skip-svg # Skip SVG regeneration
 *   php artisan deploy:refresh --force   # Force regenerate all SVGs
 */
class DeployRefresh extends Command
{
    protected $signature = 'deploy:refresh
                            {--skip-svg : Skip SVG regeneration}
                            {--skip-migrations : Skip database migrations}
                            {--force : Force regenerate all SVGs even if up to date}
                            {--no-cache : Skip cache warming in production}';

    protected $description = 'Clear all caches and regenerate SVGs for updated geometry files';

    /**
     * Topics that have geometry files and need SVG baking
     */
    protected array $geometryTopics = ['15', '16'];

    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🚀 Starting deploy refresh...');
        $this->newLine();

        // Step 1: Run pending migrations
        if (!$this->option('skip-migrations')) {
            $this->runMigrations();
        } else {
            $this->comment('⏭️  Skipping migrations (--skip-migrations)');
        }

        // Step 2: Clear all caches
        $this->clearAllCaches();

        // Step 3: Regenerate SVGs if needed
        if (!$this->option('skip-svg')) {
            $this->regenerateSvgs();
        } else {
            $this->comment('⏭️  Skipping SVG regeneration (--skip-svg)');
        }

        // Step 4: Warm caches in production
        if (app()->environment('production') && !$this->option('no-cache')) {
            $this->warmCaches();
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info("✅ Deploy refresh completed in {$elapsed}s");

        return Command::SUCCESS;
    }

    /**
     * Run pending database migrations
     */
    protected function runMigrations(): void
    {
        $this->info('🗄️  Running migrations...');

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            if ($exitCode === Command::SUCCESS) {
                if ($output !== '' && !str_contains($output, 'Nothing to migrate')) {
                    foreach (explode("\n", $output) as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $this->line("   {$line}");
                        }
                    }
                } else {
                    $this->line('   ✓ Nothing to migrate');
                }
            } else {
                $this->error("   ✗ Migrations failed (exit {$exitCode})");
                if ($output !== '') {
                    $this->line($output);
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Clear all Laravel caches
     */
    protected function clearAllCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        $caches = [
            'cache:clear' => 'Application cache',
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'Compiled views',
        ];

        foreach ($caches as $command => $description) {
            Artisan::call($command);
            $this->line("   ✓ {$description}");
        }

        $this->newLine();
    }

    /**
     * Regenerate SVGs for geometry topics where source is newer than output
     */
    protected function regenerateSvgs(): void
    {
        $this->info('🎨 Checking SVG regeneration...');

        $regenerated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->geometryTopics as $topicId) {
            $topicIdPadded = str_pad($topicId, 2, '0', STR_PAD_LEFT);
            $geometryPath = storage_path("app/tasks/topic_{$topicIdPadded}_geometry.json");
            $outputPath = storage_path("app/tasks/topic_{$topicIdPadded}.json");

            // Skip if no geometry file exists
            if (!File::exists($geometryPath)) {
                $this->line("   ⏭️  Topic {$topicId}: No geometry file");
                $skipped++;
                continue;
            }

            $needsRegeneration = $this->option('force');

            if (!$needsRegeneration) {
                // Check if geometry file is newer than output
                if (!File::exists($outputPath)) {
                    $needsRegeneration = true;
                } else {
                    $geometryTime = File::lastModified($geometryPath);
                    $outputTime = File::lastModified($outputPath);
                    $needsRegeneration = $geometryTime > $outputTime;
                }
            }

            if (!$needsRegeneration) {
                $this->line("   ✓ Topic {$topicId}: Up to date");
                $skipped++;
                continue;
            }

            // Regenerate SVGs
            $this->line("   🔄 Topic {$topicId}: Regenerating SVGs...");

            try {
                $exitCode = Artisan::call('svg:bake', ['topic' => $topicId]);

                if ($exitCode === Command::SUCCESS) {
                    $this->line("   ✓ Topic {$topicId}: SVGs regenerated");
                    $regenerated++;
                } else {
                    $this->error("   ✗ Topic {$topicId}: Failed to regenerate");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Topic {$topicId}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->comment("   Regenerated: {$regenerated}, Skipped: {$skipped}, Errors: {$errors}");
        $this->newLine();
    }

    /**
     * Warm caches in production environment
     */
    protected function warmCaches(): void
    {
        $this->info('🔥 Warming caches for production...');

        $caches = [
            'config:cache' => 'Configuration',
            'route:cache' => 'Routes',
            'view:cache' => 'Views',
        ];

        foreach ($caches as $command => $description) {
            try {
                Artisan::call($command);
                $this->line("   ✓ {$description} cached");
            } catch (\Exception $e) {
                $this->warn("   ⚠️  {$description}: " . $e->getMessage());
            }
        }

        $this->newLine();
    }
}
