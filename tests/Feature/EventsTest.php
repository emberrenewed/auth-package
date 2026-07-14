<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Events\LoginAttempted;
use Technobase\AuthKit\Events\LoginFailed;
use Technobase\AuthKit\Events\LoginSucceeded;
use Technobase\AuthKit\Events\SocialUserResolved;
use Technobase\AuthKit\Tests\TestCase;
use Technobase\AuthKit\Tests\TestUser;

beforeEach(function (): void {
    Event::fake([
        LoginAttempted::class,
        LoginSucceeded::class,
        LoginFailed::class,
        SocialUserResolved::class,
    ]);
});

it('fires LoginAttempted on every attempt', function (): void {
    /** @var TestCase $this */
    $this->createUser();

    $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertOk();

    Event::assertDispatched(LoginAttempted::class, function (LoginAttempted $event): bool {
        return $event->driver === 'password';
    });
});

it('fires LoginSucceeded on a valid login', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();

    $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertOk();

    Event::assertDispatched(LoginSucceeded::class, function (LoginSucceeded $event) use ($user): bool {
        return $event->subject instanceof TestUser
            && $event->subject->is($user)
            && $event->driver === 'password'
            && $event->flavor === 'api';
    });
});

it('fires LoginFailed with reason on bad credentials', function (): void {
    /** @var TestCase $this */
    $this->createUser();

    $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized();

    Event::assertDispatched(LoginFailed::class, function (LoginFailed $event): bool {
        return $event->driver === 'password'
            && $event->reason === 'invalid_credentials';
    });
});

it('fires SocialUserResolved after successful social login', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'google@example.com',
        'provider' => 'google',
        'provider_id' => 'google-789',
    ]);

    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('google-789');
    $socialUser->shouldReceive('getEmail')->andReturn('google@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Google User');
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with('valid-token')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/auth/google', [
        'access_token' => 'valid-token',
    ])->assertOk();

    Event::assertDispatched(SocialUserResolved::class, function (SocialUserResolved $event): bool {
        return $event->identity->provider === 'google'
            && $event->identity->providerId === 'google-789'
            && $event->subject->email === 'google@example.com';
    });
});
