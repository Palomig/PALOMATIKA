<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-time command to write VAPID keys into the production .env file.
 * Safe to run multiple times — only updates VAPID_* lines.
 *
 * Usage:
 *   php artisan vapid:set \
 *     --public="BAsp-Q430wck..." \
 *     --private="JV36iiamLtZJ..." \
 *     --subject="mailto:admin@palomatika.ru"
 */
class SetVapidKeys extends Command
{
    protected $signature   = 'vapid:set
                                {--public=  : VAPID public key (Base64URL ~88 chars)}
                                {--private= : VAPID private key (Base64URL ~44 chars)}
                                {--subject= : VAPID subject (mailto: or URL)}';

    protected $description = 'Write VAPID keys to .env for Web Push notifications';

    public function handle(): int
    {
        $publicKey  = (string) $this->option('public');
        $privateKey = (string) $this->option('private');
        $subject    = (string) ($this->option('subject') ?: 'mailto:admin@palomatika.ru');

        if ($publicKey === '' || $privateKey === '') {
            $this->error('Both --public and --private are required.');
            return self::FAILURE;
        }

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file not found at ' . $envPath);
            return self::FAILURE;
        }

        $content = file_get_contents($envPath);

        $content = $this->setEnvKey($content, 'VAPID_SUBJECT', $subject);
        $content = $this->setEnvKey($content, 'VAPID_PUBLIC_KEY', $publicKey);
        $content = $this->setEnvKey($content, 'VAPID_PRIVATE_KEY', $privateKey);

        file_put_contents($envPath, $content);

        $this->info('VAPID keys written to .env');
        $this->line('  Subject:     ' . $subject);
        $this->line('  Public key:  ' . substr($publicKey, 0, 20) . '...');
        $this->line('  Private key: ' . substr($privateKey, 0, 10) . '...');

        return self::SUCCESS;
    }

    private function setEnvKey(string $content, string $key, string $value): string
    {
        $escaped = addcslashes($value, '"');

        if (preg_match("/^{$key}=/m", $content)) {
            // Replace existing line
            return preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        }

        // Append new line
        return rtrim($content) . "\n{$key}={$value}\n";
    }
}
