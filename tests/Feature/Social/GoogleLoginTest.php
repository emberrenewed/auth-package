<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Tests\TestCase;
use Technobase\AuthKit\Tests\TestUser;

it('resolves identity via access_token and issues token', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'google@example.com',
        'provider' => 'google',
        'provider_id' => 'google-123',
    ]);

    mockSocialToken('google', 'valid-token', mockSocialUser('google-123', 'google@example.com', 'Google User'));

    $response = $this->postJson('/api/auth/google', [
        'access_token' => 'valid-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'google@example.com')
        ->assertJsonStructure(['token']);
});

it('returns 401 when access_token is invalid', function (): void {
    /** @var TestCase $this */
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')
        ->with('bad-token')
        ->andThrow(new RuntimeException('Invalid token'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->postJson('/api/auth/google', [
        'access_token' => 'bad-token',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Social authentication failed. Please try again.',
        ]);
});

it('returns 404 when no subject and auto_create is false', function (): void {
    /** @var TestCase $this */
    config()->set('auth-kit.subjects.api.auto_create_on_social', false);

    mockSocialToken('google', 'valid-token', mockSocialUser('missing-123', 'missing@example.com', 'Missing User'));

    $response = $this->postJson('/api/auth/google', [
        'access_token' => 'valid-token',
    ]);

    $response->assertNotFound()
        ->assertJson([
            'message' => 'No account is linked to this identity.',
        ]);

    expect(TestUser::query()->where('email', 'missing@example.com')->exists())->toBeFalse();
});

it('creates new user when auto_create_on_social is true', function (): void {
    /** @var TestCase $this */
    config()->set('auth-kit.subjects.api.auto_create_on_social', true);

    mockSocialToken('google', 'valid-token', mockSocialUser('new-456', 'new@example.com', 'New Google'));

    $response = $this->postJson('/api/auth/google', [
        'access_token' => 'valid-token',
    ]);

    $response->assertOk()->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com',
        'provider' => 'google',
        'provider_id' => 'new-456',
    ]);
});
