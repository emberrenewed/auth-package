<?php

declare(strict_types=1);

namespace Technobase\AuthKit;

use Illuminate\Support\ServiceProvider;
use Technobase\AuthKit\Console\InstallCommand;
use Technobase\AuthKit\Drivers\Otp\PhoneOtpDriver;
use Technobase\AuthKit\Drivers\Social\FacebookDriver;
use Technobase\AuthKit\Drivers\Social\GoogleDriver;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Support\Registry\DriverRegistry;

final class AuthKitServiceProvider extends ServiceProvider
{
    /** @var list<class-string> */
    private const SOCIAL_DRIVERS = [
        GoogleDriver::class,
        FacebookDriver::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-kit.php', 'auth-kit');

        $this->app->singleton(OtpManager::class);

        foreach (self::SOCIAL_DRIVERS as $driverClass) {
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

            $registry->register('google', fn ($app) => $app->make(GoogleDriver::class));
            $registry->register('facebook', fn ($app) => $app->make(FacebookDriver::class));
            $registry->register('phone_otp', fn ($app) => $app->make(PhoneOtpDriver::class));

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
