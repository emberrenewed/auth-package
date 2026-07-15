<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Events\Auth\LoginAttempted;
use Technobase\AuthKit\Events\Auth\LoginFailed;
use Technobase\AuthKit\Events\Auth\LoginSucceeded;
use Technobase\AuthKit\Events\Social\SocialUserResolved;
use Technobase\AuthKit\Tests\TestCase;

beforeEach(function (): void {
    Event::fake([
        LoginAttempted::class,
        LoginSucceeded::class,
        LoginFailed::class,
        SocialUserResolved::class,
    ]);
});

it('fires LoginAttempted on google login', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'google@example.com',
        'provider' => 'google',
        'provider_id' => 'google-1',
    ]);

    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('google-1');
    $socialUser->shouldReceive('getEmail')->andReturn('google@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Google User');
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with('token')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/auth/google', ['access_token' => 'token'])->assertOk();

    Event::assertDispatched(LoginAttempted::class, fn (LoginAttempted $event): bool => $event->driver === 'google');
});

it('fires LoginSucceeded on valid google login', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser([
        'email' => 'ok@example.com',
        'provider' => 'google',
        'provider_id' => 'google-ok',
    ]);

    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('google-ok');
    $socialUser->shouldReceive('getEmail')->andReturn('ok@example.com');
    $socialUser->shouldReceive('getName')->andReturn('OK');
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with('ok-token')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/auth/google', ['access_token' => 'ok-token'])->assertOk();

    Event::assertDispatched(LoginSucceeded::class, function (LoginSucceeded $event) use ($user): bool {
        return $event->subject->is($user)
            && $event->driver === 'google'
            && $event->flavor === 'api';
    });
});

it('fires LoginFailed when google token is invalid', function (): void {
    /** @var TestCase $this */
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->andThrow(new RuntimeException('bad token'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/auth/google', ['access_token' => 'bad'])->assertUnauthorized();

    Event::assertDispatched(LoginFailed::class, function (LoginFailed $event): bool {
        return $event->driver === 'google'
            && $event->reason === 'google_authentication_failed';
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
