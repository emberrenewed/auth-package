<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Drivers\Social\GoogleDriver;
use Technobase\AuthKit\Exceptions\InvalidCredentialsException;
use Technobase\AuthKit\Http\CredentialIssuers\SanctumCredentialIssuer;
use Technobase\AuthKit\Support\Registry\DriverRegistry;
use Technobase\AuthKit\Tests\TestCase;

it('validates empty payload for web google driver', function (): void {
    $driver = new GoogleDriver(flavor: 'web');
    $request = Request::create('/auth/google/callback', 'GET');

    expect($driver->validate($request))->toBe([]);
});

it('throws when socialite provider is not an oauth2 abstract provider', function (): void {
    $provider = Mockery::mock();
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $driver = new GoogleDriver(flavor: 'api');

    expect(fn () => $driver->resolveIdentity(['access_token' => 'x']))
        ->toThrow(InvalidCredentialsException::class);
});

it('clears cached drivers when extend is called', function (): void {
    $registry = app(DriverRegistry::class);
    $first = $registry->driver('google');

    $registry->extend('google', fn ($app) => $app->make(GoogleDriver::class));

    $second = $registry->driver('google');

    expect($first)->not->toBe($second);
});

it('requires createToken support on sanctum issuer', function (): void {
    $subject = new class implements Authenticatable
    {
        public function getAuthIdentifierName()
        {
            return 'id';
        }

        public function getAuthIdentifier()
        {
            return 1;
        }

        public function getAuthPasswordName()
        {
            return 'password';
        }

        public function getAuthPassword()
        {
            return 'x';
        }

        public function getRememberToken()
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName()
        {
            return 'remember_token';
        }
    };

    $issuer = new SanctumCredentialIssuer;

    expect(fn () => $issuer->issue($subject, Request::create('/')))
        ->toThrow(RuntimeException::class);
});

it('blocks banned subjects after social login', function (): void {
    /** @var TestCase $this */
    $model = new class extends AuthenticatableUser
    {
        protected $table = 'users';

        protected $guarded = [];

        public function createToken(string $name, array $abilities = ['*'], $expiresAt = null)
        {
            throw new RuntimeException('should not issue token for banned user');
        }
    };

    $model->newQuery()->create([
        'first_name' => 'Ban',
        'last_name' => 'Ned',
        'email' => 'banned-plain@example.com',
        'password' => bcrypt('password'),
        'provider' => 'google',
        'provider_id' => 'banned-google',
        'banned_at' => now(),
    ]);

    config()->set('auth.providers.users.model', $model::class);
    config()->set('auth-kit.subjects.api.model', $model::class);

    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('banned-google');
    $socialUser->shouldReceive('getEmail')->andReturn('banned-plain@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Banned');
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with('token')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/auth/google', ['access_token' => 'token'])->assertForbidden();
});
