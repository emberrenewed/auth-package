<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends reset link always returning 200 regardless of email', function (): void {
    Notification::fake();

    $this->createUser();

    $existing = $this->postJson('/api/auth/forgot-password', [
        'email' => 'user@example.com',
    ]);

    $unknown = $this->postJson('/api/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $existing->assertOk()->assertJson([
        'message' => __('auth-kit::auth-kit.reset_sent'),
    ]);
    $unknown->assertOk()->assertJson([
        'message' => __('auth-kit::auth-kit.reset_sent'),
    ]);
});

it('resets password with valid token and revokes all tokens', function (): void {
    $user = $this->createUser();

    $tokenA = $user->createToken('a')->plainTextToken;
    $tokenB = $user->createToken('b')->plainTextToken;

    expect($user->tokens()->count())->toBe(2);

    $resetToken = Password::broker('users')->createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => $resetToken,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk()->assertJson([
        'message' => __('auth-kit::auth-kit.reset_done'),
    ]);

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(0);

    // Ensure old tokens are no longer usable identifiers in DB.
    expect(DB::table('personal_access_tokens')->count())->toBe(0);
    expect($tokenA)->not->toBe($tokenB);
});

it('rejects an expired token with 422', function (): void {
    $user = $this->createUser();

    $resetToken = Password::broker('users')->createToken($user);

    DB::table('password_reset_tokens')->where('email', $user->email)->update([
        'created_at' => now()->subHours(2),
    ]);

    config()->set('auth.passwords.users.expire', 60);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => $resetToken,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(422);
});
