<?php

declare(strict_types=1);

namespace Technobase\AuthKit;

use Illuminate\Support\ServiceProvider;
use Technobase\AuthKit\Console\InstallCommand;
use Technobase\AuthKit\Drivers\EmailOtpDriver;
use Technobase\AuthKit\Drivers\FacebookDriver;
use Technobase\AuthKit\Drivers\GithubDriver;
use Technobase\AuthKit\Drivers\GoogleDriver;
use Technobase\AuthKit\Drivers\PasswordDriver;
use Technobase\AuthKit\Drivers\WhatsAppOtpDriver;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Support\DriverRegistry;

final class AuthKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-kit.php', 'auth-kit');

        $this->app->singleton(OtpManager::class);

        $this->app->bind(PasswordDriver::class, function ($app): PasswordDriver {
            return new PasswordDriver(
                flavor: $app->bound('auth-kit.flavor')
                    ? $app->make('auth-kit.flavor')
                    : 'web',
                request: $app->make('request'),
            );
        });

        foreach ([GoogleDriver::class, FacebookDriver::class, GithubDriver::class] as $driverClass) {
            $this->app->bind($driverClass, function ($app) use ($driverClass) {
                return new $driverClass(
                    flavor: $app->bound('auth-kit.flavor')
                        ? $app->make('auth-kit.flavor')
                        : 'api',
                );
            });
        }

        $this->app->singleton(DriverRegistry::class, function ($app): DriverRegistry {
            $registry = new DriverRegistry;

            $registry->register('password', fn ($app) => $app->make(PasswordDriver::class));
            $registry->register('google', fn ($app) => $app->make(GoogleDriver::class));
            $registry->register('facebook', fn ($app) => $app->make(FacebookDriver::class));
            $registry->register('github', fn ($app) => $app->make(GithubDriver::class));
            $registry->register('email_otp', fn ($app) => $app->make(EmailOtpDriver::class));
            $registry->register('whatsapp_otp', fn ($app) => $app->make(WhatsAppOtpDriver::class));

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/auth-kit.php' => config_path('auth-kit.php'),
        ], 'auth-kit-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'auth-kit-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }

        if (config('auth-kit.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
