<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

it('authenticates via facebook access_token', function (): void {
    $this->createUser([
        'email' => 'facebook@example.com',
        'provider' => 'facebook',
        'provider_id' => 'fb-123',
    ]);

    mockSocialToken('facebook', 'valid-token', mockSocialUser('fb-123', 'facebook@example.com', 'FB User'));

    $this->postJson('/api/auth/facebook', [
        'access_token' => 'valid-token',
    ])->assertOk()
        ->assertJsonPath('data.user.email', 'facebook@example.com')
        ->assertJsonStructure(['token']);
});

it('authenticates via github access_token', function (): void {
    $this->createUser([
        'email' => 'github@example.com',
        'provider' => 'github',
        'provider_id' => 'gh-123',
    ]);

    mockSocialToken('github', 'valid-token', mockSocialUser('gh-123', 'github@example.com', 'GH User'));

    $this->postJson('/api/auth/github', [
        'access_token' => 'valid-token',
    ])->assertOk()
        ->assertJsonPath('data.user.email', 'github@example.com')
        ->assertJsonStructure(['token']);
});

it('returns 401 when facebook token is invalid', function (): void {
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')
        ->with('bad-token')
        ->andThrow(new RuntimeException('Invalid token'));

    Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);

    $this->postJson('/api/auth/facebook', [
        'access_token' => 'bad-token',
    ])->assertUnauthorized()
        ->assertJson([
            'message' => __('auth-kit::auth-kit.social_failed'),
        ]);
});
