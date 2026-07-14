<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Technobase\AuthKit\Notifications\EmailOtpNotification;
use Technobase\AuthKit\Tests\TestCase;

it('sends an email otp and stores a hashed code', function (): void {
    /** @var TestCase $this */
    Notification::fake();

    $this->createUser(['email' => 'otp@example.com']);

    $this->postJson('/api/auth/otp/email/send', [
        'email' => 'otp@example.com',
    ])->assertOk()
        ->assertJson([
            'message' => 'If that destination is valid, we sent a code.',
        ]);

    Notification::assertSentOnDemand(EmailOtpNotification::class);

    $this->assertDatabaseHas('auth_kit_otps', [
        'channel' => 'email',
        'destination' => 'otp@example.com',
    ]);
});

it('verifies email otp and issues a sanctum token', function (): void {
    /** @var TestCase $this */
    $this->createUser(['email' => 'verify-otp@example.com']);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'email',
        'destination' => 'verify-otp@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/email/verify', [
        'email' => 'verify-otp@example.com',
        'code' => '123456',
    ])->assertOk()
        ->assertJsonStructure(['token'])
        ->assertJsonPath('data.user.email', 'verify-otp@example.com');

    $this->assertDatabaseMissing('auth_kit_otps', [
        'channel' => 'email',
        'destination' => 'verify-otp@example.com',
    ]);
});

it('rejects wrong email otp codes', function (): void {
    /** @var TestCase $this */
    $this->createUser(['email' => 'wrong-otp@example.com']);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'email',
        'destination' => 'wrong-otp@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/otp/email/verify', [
        'email' => 'wrong-otp@example.com',
        'code' => '000000',
    ])->assertUnauthorized();
});

it('rejects expired email otp codes', function (): void {
    /** @var TestCase $this */
    $this->createUser(['email' => 'expired-otp@example.com']);

    DB::table('auth_kit_otps')->insert([
        'channel' => 'email',
        'destination' => 'expired-otp@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->subMinute(),
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ]);

    $this->postJson('/api/auth/otp/email/verify', [
        'email' => 'expired-otp@example.com',
        'code' => '123456',
    ])->assertUnauthorized();
});
