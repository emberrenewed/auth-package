<?php

declare(strict_types=1);

use Technobase\AuthKit\Support\Registry\AuthKitDrivers;

it('lists enabled api providers', function (): void {
    $response = $this->getJson('/api/auth/providers');

    $response->assertOk();

    expect($response->json('data'))->toEqual([
        'google',
        'facebook',
        'phone_otp',
    ]);
});

it('hides disabled drivers from the providers list', function (): void {
    config()->set('auth-kit.drivers.api', [
        'google' => true,
        'facebook' => false,
        'phone_otp' => false,
    ]);

    $response = $this->getJson('/api/auth/providers');

    $response->assertOk();

    expect($response->json('data'))->toEqual([
        'google',
    ]);
});

it('rejects authentication for a disabled social driver', function (): void {
    config()->set('auth-kit.drivers.api', [
        'google' => false,
        'facebook' => true,
        'phone_otp' => false,
    ]);

    $this->postJson('/api/auth/google', ['access_token' => 'token'])
        ->assertNotFound()
        ->assertJsonPath('message', 'These credentials do not match our records.');
});

it('resolves enabled drivers from boolean maps and legacy lists', function (): void {
    config()->set('auth-kit.drivers.api', [
        'google' => true,
        'facebook' => false,
        'phone_otp' => true,
    ]);

    expect(AuthKitDrivers::enabled('api'))->toBe(['google', 'phone_otp'])
        ->and(AuthKitDrivers::isEnabled('api', 'google'))->toBeTrue()
        ->and(AuthKitDrivers::isEnabled('api', 'facebook'))->toBeFalse();

    config()->set('auth-kit.drivers.web', ['google', 'facebook']);

    expect(AuthKitDrivers::enabled('web'))->toBe(['google', 'facebook'])
        ->and(AuthKitDrivers::isEnabled('web', 'facebook'))->toBeTrue()
        ->and(AuthKitDrivers::isEnabled('web', 'phone_otp'))->toBeFalse();
});
