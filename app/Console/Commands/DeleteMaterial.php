<?php

namespace App\Console\Commands;

use App\Models\JarvisMaterial;
use Illuminate\Console\Command;

class DeleteMaterial extends Command
{
    protected $signature = 'materials:delete {slug}';
    protected $description = 'Delete a material by slug (DB record + static HTML file)';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $material = JarvisMaterial::where('slug', $slug)->first();
        if ($material) {
            $material->delete();
            $this->line("DB record deleted: {$slug}");
        } else {
            $this->line("No DB record found for: {$slug}");
        }

        $path = public_path('materials/' . $slug . '.html');
        if (file_exists($path)) {
            unlink($path);
            $this->line("Static file deleted: {$path}");
        } else {
            $this->line("No static file found at: {$path}");
        }

        $this->info("Done.");
        return 0;
    }
}
