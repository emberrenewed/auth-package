<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Technobase\AuthKit\Otp\Channels\LogOtpChannel;
use Technobase\AuthKit\Tests\TestCase;

it('sends an iraqi phone otp and reports the carrier', function (): void {
    /** @var TestCase $this */
    config()->set('auth-kit.otp.channels.sms', LogOtpChannel::class);

    $this->createUser([
        'email' => 'iraq@example.com',
        'phone' => '9647501234567',
    ]);

    $this->postJson('/api/auth/otp/phone/send', [
        'phone' => '0750 123 4567',
    ])->assertOk()
        ->assertJson([
            'message' => 'If that destination is valid, we sent a code.',
            'carrier' => 'korek',
            'carrier_label' => 'Korek Telecom',
        ]);

    $this->assertDatabaseHas('auth_kit_otps', [
        'channel' => 'sms',
        'destination' => '9647501234567',
    ]);
});

it('rejects non-iraqi numbers for phone otp', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/auth/otp/phone/send', [
        'phone' => '+15551234567',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['phone']);
});

it('verifies iraqi phone otp for asiacell and issues a token', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'asiacell@example.com',
        'phone' => '9647701234567',
    ]);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'sms',
        'destination' => '9647701234567',
        'code_hash' => Hash::make('112233'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/phone/verify', [
        'phone' => '+9647701234567',
        'code' => '112233',
    ])->assertOk()
        ->assertJsonStructure(['token']);
});

it('verifies zain iraq phone otp', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'zain@example.com',
        'phone' => '9647901234567',
    ]);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'sms',
        'destination' => '9647901234567',
        'code_hash' => Hash::make('445566'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/phone/verify', [
        'phone' => '07901234567',
        'code' => '445566',
    ])->assertOk()
        ->assertJsonStructure(['token']);
});
