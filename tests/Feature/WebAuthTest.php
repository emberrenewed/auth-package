<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Technobase\AuthKit\Tests\TestCase;

beforeEach(function (): void {
    Route::get('/home', fn () => 'home')->name('home');
    Route::get('/register/complete', fn () => 'complete')->name('register.complete');
    Route::get('/login', fn () => 'login')->name('login');
    Route::get('/reset-password/{token}', fn () => 'reset')->name('password.reset');
});

it('logs in via web password driver and redirects home', function (): void {
    /** @var TestCase $this */
    $this->createUser();

    $response = $this->from('/login')->post('/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

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

it('sends web forgot password with status flash', function (): void {
    /** @var TestCase $this */
    Notification::fake();

    $this->createUser();

    $this->from('/forgot')
        ->post('/auth/forgot-password', ['email' => 'user@example.com'])
        ->assertRedirect('/forgot')
        ->assertSessionHas('status');
});
