<?php

declare(strict_types=1);

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Technobase\AuthKit\Events\LoggedOut;
use Technobase\AuthKit\Http\Actions\RedirectToProviderAction;
use Technobase\AuthKit\Tests\TestCase;
use Technobase\AuthKit\Tests\TestUser;

beforeEach(function (): void {
    Route::get('/home', fn () => 'home')->name('home');
    Route::get('/login', fn () => 'login')->name('login');
    Route::get('/register/complete', fn () => 'complete')->name('register.complete');
});

it('logs out web session and fires LoggedOut', function (): void {
    /** @var TestCase $this */
    Event::fake([LoggedOut::class]);

    $user = $this->createUser();

    $this->actingAs($user)
        ->post('/auth/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    Event::assertDispatched(LoggedOut::class, function (LoggedOut $event) use ($user): bool {
        return $event->subject instanceof TestUser
            && $event->subject->is($user)
            && $event->flavor === 'web';
    });
});

it('resets password on web and flashes status', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();
    /** @var PasswordBroker $broker */
    $broker = Password::broker('users');
    $token = $broker->createToken($user);

    $this->from('/reset')
        ->post('/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/reset')
        ->assertSessionHas('status');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects invalid web reset token with errors', function (): void {
    /** @var TestCase $this */
    $this->createUser();

    $this->from('/reset')
        ->post('/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/reset')
        ->assertSessionHasErrors('email');
});

it('returns validation errors for failed web password login', function (): void {
    /** @var TestCase $this */
    $this->createUser();

    $this->from('/login')
        ->post('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');
});

it('returns errors when redirect driver is not registered', function (): void {
    config()->set('auth-kit.drivers.web', ['password', 'apple']);

    $response = app(RedirectToProviderAction::class)('apple');

    expect($response->isRedirect())->toBeTrue();
});

it('redirects web social users without a subject to registration completion', function (): void {
    /** @var TestCase $this */
    config()->set('auth-kit.subjects.web.auto_create_on_social', false);

    $socialUser = (new SocialiteUser)->map([
        'id' => 'missing-web',
        'name' => 'Missing',
        'email' => 'missing-web@example.com',
        'avatar' => null,
    ]);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')
        ->assertRedirect(route('register.complete'));
});
