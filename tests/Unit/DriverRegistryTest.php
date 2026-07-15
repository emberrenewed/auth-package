<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Technobase\AuthKit\Contracts\Drivers\AuthDriver;
use Technobase\AuthKit\Drivers\Social\GoogleDriver;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;
use Technobase\AuthKit\Support\Registry\DriverRegistry;

it('resolves a built-in driver by name', function (): void {
    $registry = app(DriverRegistry::class);

    $driver = $registry->driver('google');

    expect($driver)->toBeInstanceOf(GoogleDriver::class)
        ->and($driver->name())->toBe('google');
});

it('registers and resolves a custom driver', function (): void {
    $registry = app(DriverRegistry::class);

    $registry->register('custom', function (): AuthDriver {
        return new class implements AuthDriver
        {
            public function name(): string
            {
                return 'custom';
            }

            public function validate(Request $request): array
            {
                return [];
            }

            public function resolveIdentity(array $payload): NormalizedIdentity
            {
                return new NormalizedIdentity(
                    provider: 'custom',
                    providerId: '1',
                    email: 'custom@example.com',
                    name: 'Custom User',
                    avatar: null,
                );
            }
        };
    });

    expect($registry->driver('custom')->name())->toBe('custom')
        ->and($registry->drivers())->toContain('custom');
});

it('throws DriverNotFoundException for unknown driver name', function (): void {
    $registry = app(DriverRegistry::class);

    $registry->driver('does-not-exist');
})->throws(DriverNotFoundException::class);
