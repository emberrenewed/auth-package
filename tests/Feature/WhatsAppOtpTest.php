<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Technobase\AuthKit\Otp\Channels\LogOtpChannel;

it('sends a whatsapp otp via the log channel in tests', function (): void {
    config()->set('auth-kit.otp.channels.whatsapp', LogOtpChannel::class);

    $this->createUser([
        'email' => 'wa@example.com',
        'phone' => '15551234567',
    ]);

    $this->postJson('/api/auth/otp/whatsapp/send', [
        'phone' => '+1 (555) 123-4567',
    ])->assertOk()
        ->assertJson([
            'message' => 'If that destination is valid, we sent a code.',
        ]);

    $this->assertDatabaseHas('auth_kit_otps', [
        'channel' => 'whatsapp',
        'destination' => '15551234567',
    ]);
});

it('verifies whatsapp otp by phone and issues a token', function (): void {
    $this->createUser([
        'email' => 'wa-verify@example.com',
        'phone' => '15559876543',
    ]);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'whatsapp',
        'destination' => '15559876543',
        'code_hash' => Hash::make('654321'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/whatsapp/verify', [
        'phone' => '15559876543',
        'code' => '654321',
    ])->assertOk()
        ->assertJsonStructure(['token']);
});

it('rejects invalid whatsapp otp codes', function (): void {
    $this->createUser([
        'email' => 'wa-bad@example.com',
        'phone' => '15550001111',
    ]);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'whatsapp',
        'destination' => '15550001111',
        'code_hash' => Hash::make('654321'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/whatsapp/verify', [
        'phone' => '15550001111',
        'code' => '000000',
    ])->assertUnauthorized();
});
