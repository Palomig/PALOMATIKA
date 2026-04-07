<?php

namespace App\Console\Commands;

use App\Models\JarvisMaterial;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillMaterials extends Command
{
    protected $signature = 'materials:backfill';
    protected $description = 'Create DB records for static HTML files in public/materials/ that are missing from jarvis_materials';

    public function handle(): int
    {
        $dir = public_path('materials');

        if (!is_dir($dir)) {
            $this->error("Directory not found: {$dir}");
            return 1;
        }

        $adminUser = User::where('role', 'admin')->orderBy('id')->first();
        if (!$adminUser) {
            $this->error('No admin user found.');
            return 1;
        }

        $existingSlugs = JarvisMaterial::pluck('slug')->flip();

        $files = glob($dir . '/*.html');
        $created = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $slug = basename($file, '.html');

            if ($existingSlugs->has($slug)) {
                $skipped++;
                continue;
            }

            $html = file_get_contents($file);

            // Extract <title>
            preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $tm);
            $title = isset($tm[1]) ? trim(strip_tags($tm[1])) : $slug;

            // Extract <meta name="description">
            preg_match('/<meta\s[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\'][^>]*>/i', $html, $em);
            if (!isset($em[1])) {
                preg_match('/<meta\s[^>]*content=["\'](.*?)["\'][^>]*name=["\']description["\'][^>]*>/i', $html, $em);
            }
            $excerpt = isset($em[1]) ? trim($em[1]) : null;

            JarvisMaterial::create([
                'owner_teacher_id' => $adminUser->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'category' => null,
                'source_content' => $html,
                'status' => JarvisMaterial::STATUS_PUBLISHED,
                'published_at' => \Carbon\Carbon::createFromTimestamp(filemtime($file)),
            ]);

            $this->line("  + {$slug}");
            $created++;
        }

        $this->info("Done. Created: {$created}, skipped (already in DB): {$skipped}.");
        return 0;
    }
}
