<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class InstallCommand extends Command
{
    protected $signature = 'auth-kit:install
                            {--force : Overwrite existing OAuth/OTP env placeholders}';

    protected $description = 'Publish Auth Kit assets and auto-add social/OTP keys to .env / services.php';

    /** @var array<string, string> */
    private const ENV_DEFAULTS = [
        'GOOGLE_CLIENT_ID' => '',
        'GOOGLE_CLIENT_SECRET' => '',
        'GOOGLE_REDIRECT_URI' => '"${APP_URL}/auth/google/callback"',
        'FACEBOOK_CLIENT_ID' => '',
        'FACEBOOK_CLIENT_SECRET' => '',
        'FACEBOOK_REDIRECT_URI' => '"${APP_URL}/auth/facebook/callback"',
        'GITHUB_CLIENT_ID' => '',
        'GITHUB_CLIENT_SECRET' => '',
        'GITHUB_REDIRECT_URI' => '"${APP_URL}/auth/github/callback"',
        'WHATSAPP_TOKEN' => '',
        'WHATSAPP_PHONE_NUMBER_ID' => '',
        'WHATSAPP_OTP_TEMPLATE' => 'auth_otp',
    ];

    /** @var array<string, array{client_id: string, client_secret: string, redirect: string}> */
    private const SERVICES = [
        'google' => [
            'client_id' => 'GOOGLE_CLIENT_ID',
            'client_secret' => 'GOOGLE_CLIENT_SECRET',
            'redirect' => 'GOOGLE_REDIRECT_URI',
        ],
        'facebook' => [
            'client_id' => 'FACEBOOK_CLIENT_ID',
            'client_secret' => 'FACEBOOK_CLIENT_SECRET',
            'redirect' => 'FACEBOOK_REDIRECT_URI',
        ],
        'github' => [
            'client_id' => 'GITHUB_CLIENT_ID',
            'client_secret' => 'GITHUB_CLIENT_SECRET',
            'redirect' => 'GITHUB_REDIRECT_URI',
        ],
    ];

    public function handle(): int
    {
        $this->publishAssets();
        $this->ensureEnvFile(base_path('.env'));
        $this->ensureEnvFile(base_path('.env.example'));
        $this->ensureServices();

        $this->newLine();
        $this->info('Auth Kit installed.');
        $this->line('Fill .env with Google / Facebook / GitHub / WhatsApp credentials, then:');
        $this->line('  php artisan migrate');
        $this->line('  php artisan serve');

        return self::SUCCESS;
    }

    private function publishAssets(): void
    {
        $this->callSilent('vendor:publish', [
            '--tag' => 'auth-kit-config',
            '--force' => true,
        ]);

        $this->callSilent('vendor:publish', [
            '--tag' => 'auth-kit-migrations',
            '--force' => true,
        ]);

        $this->components->info('Published auth-kit config + migrations.');
    }

    private function ensureEnvFile(string $path): void
    {
        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);
        $added = [];

        foreach (self::ENV_DEFAULTS as $key => $default) {
            if (preg_match('/^'.preg_quote($key, '/').'=/m', $contents) === 1) {
                if ($this->option('force') && $default === '') {
                    $contents = preg_replace(
                        '/^'.preg_quote($key, '/').'=.*$/m',
                        $key.'=',
                        $contents,
                    ) ?? $contents;
                }

                continue;
            }

            $added[] = $key;
            $contents = rtrim($contents)."\n{$key}={$default}\n";
        }

        if ($added !== []) {
            $contents = rtrim($contents)."\n";
            File::put($path, $contents);
            $this->components->twoColumnDetail(basename($path), 'Added '.implode(', ', $added));
        } else {
            $this->components->twoColumnDetail(basename($path), 'Auth Kit keys already present');
        }
    }

    private function ensureServices(): void
    {
        $path = config_path('services.php');

        if (! File::exists($path)) {
            $this->components->warn('config/services.php not found — skipped.');

            return;
        }

        $contents = File::get($path);

        foreach (self::SERVICES as $name => $env) {
            if (preg_match("/['\"]".preg_quote($name, '/')."['\"]\s*=>/", $contents) === 1) {
                $this->components->twoColumnDetail('config/services.php', "{$name} already configured");

                continue;
            }

            $block = <<<PHP

    '{$name}' => [
        'client_id' => env('{$env['client_id']}'),
        'client_secret' => env('{$env['client_secret']}'),
        'redirect' => env('{$env['redirect']}'),
    ],
PHP;

            $updated = preg_replace('/\];\s*$/', $block."\n];\n", $contents, 1);

            if ($updated === null || $updated === $contents) {
                $this->components->warn("Could not auto-edit config/services.php for {$name}.");

                continue;
            }

            $contents = $updated;
            File::put($path, $contents);
            $this->components->twoColumnDetail('config/services.php', "Added {$name} Socialite config");
        }

        $this->ensureWhatsAppServices($path, $contents);
    }

    private function ensureWhatsAppServices(string $path, string $contents): void
    {
        if (preg_match("/['\"]whatsapp['\"]\s*=>/", $contents) === 1) {
            $this->components->twoColumnDetail('config/services.php', 'whatsapp already configured');

            return;
        }

        $block = <<<'PHP'

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'otp_template' => env('WHATSAPP_OTP_TEMPLATE', 'auth_otp'),
    ],
PHP;

        $updated = preg_replace('/\];\s*$/', $block."\n];\n", $contents, 1);

        if ($updated === null || $updated === $contents) {
            $this->components->warn('Could not auto-edit config/services.php for whatsapp.');

            return;
        }

        File::put($path, $updated);
        $this->components->twoColumnDetail('config/services.php', 'Added whatsapp Cloud API config');
    }
}
