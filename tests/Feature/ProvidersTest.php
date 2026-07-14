<?php

declare(strict_types=1);

use Technobase\AuthKit\Support\AuthKitDrivers;

it('lists enabled api providers', function (): void {
    $response = $this->getJson('/api/auth/providers');

    $response->assertOk();

    expect($response->json('data'))->toEqual([
        'password',
        'google',
        'facebook',
        'github',
        'email_otp',
        'whatsapp_otp',
    ]);
});

it('hides disabled drivers from the providers list', function (): void {
    config()->set('auth-kit.drivers.api', [
        'password' => true,
        'google' => true,
        'facebook' => false,
        'github' => false,
        'email_otp' => false,
        'whatsapp_otp' => false,
    ]);

    $response = $this->getJson('/api/auth/providers');

    $response->assertOk();

    expect($response->json('data'))->toEqual([
        'password',
        'google',
    ]);
});

it('rejects authentication for a disabled social driver', function (): void {
    config()->set('auth-kit.drivers.api', [
        'password' => true,
        'google' => false,
        'facebook' => true,
        'github' => false,
    ]);

    $this->postJson('/api/auth/google', ['access_token' => 'token'])
        ->assertNotFound()
        ->assertJsonPath('message', 'These credentials do not match our records.');
});

it('resolves enabled drivers from boolean maps and legacy lists', function (): void {
    config()->set('auth-kit.drivers.api', [
        'password' => true,
        'google' => true,
        'facebook' => false,
    ]);

    expect(AuthKitDrivers::enabled('api'))->toBe(['password', 'google'])
        ->and(AuthKitDrivers::isEnabled('api', 'google'))->toBeTrue()
        ->and(AuthKitDrivers::isEnabled('api', 'facebook'))->toBeFalse();

    config()->set('auth-kit.drivers.web', ['password', 'facebook']);

    expect(AuthKitDrivers::enabled('web'))->toBe(['password', 'facebook'])
        ->and(AuthKitDrivers::isEnabled('web', 'facebook'))->toBeTrue()
        ->and(AuthKitDrivers::isEnabled('web', 'google'))->toBeFalse();
});
