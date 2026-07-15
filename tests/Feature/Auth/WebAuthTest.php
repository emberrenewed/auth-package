<?php

declare(strict_types=1);

use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Http\Actions\Social\RedirectToProviderAction;
use Technobase\AuthKit\Tests\TestCase;

it('redirects to google oauth for web social driver', function (): void {
    /** @var TestCase $this */
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/redirect')
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

it('completes web google callback and logs the user in', function (): void {
    /** @var TestCase $this */
    $this->createUser([
        'email' => 'google@example.com',
        'provider' => 'google',
        'provider_id' => 'web-google-1',
    ]);

    $socialUser = Mockery::mock(User::class);
    $socialUser->shouldReceive('getId')->andReturn('web-google-1');
    $socialUser->shouldReceive('getEmail')->andReturn('google@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Google User');
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

it('logs out web session', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->actingAs($user);

    $this->post('/auth/logout')->assertRedirect();
    $this->assertGuest();
});

it('returns errors when redirect driver is not registered', function (): void {
    config()->set('auth-kit.drivers.web', ['google', 'myspace']);

    $response = app(RedirectToProviderAction::class)('myspace');

    expect($response->isRedirect())->toBeTrue();
});

it('redirects web social users without a subject to registration completion', function (): void {
    /** @var TestCase $this */
    config()->set('auth-kit.subjects.web.auto_create_on_social', false);

    $socialUser = (new Laravel\Socialite\Two\User)->map([
        'id' => 'missing-web',
        'name' => 'Missing',
        'email' => 'missing-web@example.com',
        'avatar' => null,
    ]);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')->assertRedirect(route('register.complete'));
});
