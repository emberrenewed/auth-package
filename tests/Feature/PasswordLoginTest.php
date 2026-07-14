<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    RateLimiter::clear('auth_kit_login:'.md5('user@example.com|127.0.0.1'));
    RateLimiter::clear('auth_kit_login:'.md5('unknown@example.com|127.0.0.1'));
});

it('issues sanctum token on correct credentials', function (): void {
    $this->createUser();

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'email'],
            ],
            'token',
        ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('returns 401 on wrong password without leaking email', function (): void {
    $this->createUser();

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'These credentials do not match our records.',
        ])
        ->assertJsonMissing(['email' => 'user@example.com']);
});

it('returns identical 401 for unknown email', function (): void {
    $this->createUser();

    $wrongPassword = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    RateLimiter::clear('auth_kit_login:'.md5('user@example.com|127.0.0.1'));

    $unknownEmail = $this->postJson('/api/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ]);

    $wrongPassword->assertUnauthorized();
    $unknownEmail->assertUnauthorized();

    expect($wrongPassword->json('message'))->toBe($unknownEmail->json('message'));
});

it('throttles after max_attempts and returns 401', function (): void {
    $this->createUser();

    $maxAttempts = (int) config('auth-kit.throttle.max_attempts', 5);

    for ($i = 0; $i < $maxAttempts; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
    expect($response->json('message'))->toContain('Too many login attempts');
});

it('clears throttle counter on successful login', function (): void {
    $this->createUser();

    $maxAttempts = (int) config('auth-kit.throttle.max_attempts', 5);

    for ($i = 0; $i < $maxAttempts - 1; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertOk();

    for ($i = 0; $i < $maxAttempts; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized()
        ->assertJsonPath('message', fn ($message) => str_contains((string) $message, 'Too many login attempts'));
});

it('blocks a banned subject with 403', function (): void {
    $this->createUser([
        'banned_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertForbidden()
        ->assertJson([
            'message' => 'This account has been suspended.',
        ]);
});
