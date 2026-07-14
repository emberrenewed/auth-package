<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Technobase\AuthKit\Contracts\Drivers\AuthDriver;
use Technobase\AuthKit\Drivers\Password\PasswordDriver;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Support\Registry\DriverRegistry;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

it('resolves a built-in driver by name', function (): void {
    $registry = app(DriverRegistry::class);

    $driver = $registry->driver('password');

    expect($driver)->toBeInstanceOf(PasswordDriver::class)
        ->and($driver->name())->toBe('password');
});

it('registers and resolves a custom driver', function (): void {
    $registry = app(DriverRegistry::class);

    $registry->register('apple', function (): AuthDriver {
        return new class implements AuthDriver
        {
            public function name(): string
            {
                return 'apple';
            }

            public function validate(Request $request): array
            {
                return [];
            }

            public function resolveIdentity(array $payload): NormalizedIdentity
            {
                return new NormalizedIdentity(
                    provider: 'apple',
                    providerId: '1',
                    email: 'apple@example.com',
                    name: 'Apple User',
                    avatar: null,
                );
            }
        };
    });

    expect($registry->driver('apple')->name())->toBe('apple')
        ->and($registry->drivers())->toContain('apple');
});

it('throws DriverNotFoundException for unknown driver name', function (): void {
    $registry = app(DriverRegistry::class);

    $registry->driver('does-not-exist');
})->throws(DriverNotFoundException::class);
