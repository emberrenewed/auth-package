<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Technobase\AuthKit\AuthKitServiceProvider;
use Technobase\AuthKit\Otp\Channels\LogOtpChannel;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            SocialiteServiceProvider::class,
            AuthKitServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.url', 'http://localhost');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.passwords.users', [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);

        $app['config']->set('auth-kit.subjects.web.model', TestUser::class);
        $app['config']->set('auth-kit.subjects.api.model', TestUser::class);
        $app['config']->set('auth-kit.subjects.api.lookup_columns', ['email', 'phone']);
        $app['config']->set('auth-kit.subjects.web.auto_create_on_social', true);
        $app['config']->set('auth-kit.subjects.api.auto_create_on_social', false);
        $app['config']->set('auth-kit.drivers.web', ['password', 'google', 'facebook', 'github']);
        $app['config']->set('auth-kit.drivers.api', [
            'password',
            'google',
            'facebook',
            'github',
            'email_otp',
            'whatsapp_otp',
        ]);
        $app['config']->set('auth-kit.throttle.max_attempts', 5);
        $app['config']->set('auth-kit.throttle.decay_minutes', 1);
        $app['config']->set('auth-kit.password_reset.broker', 'users');
        $app['config']->set('auth-kit.routes.enabled', true);
        $app['config']->set('auth-kit.otp.channels.whatsapp', LogOtpChannel::class);

        $app['config']->set('services.google', [
            'client_id' => 'testing-client-id',
            'client_secret' => 'testing-client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);
        $app['config']->set('services.facebook', [
            'client_id' => 'testing-facebook-id',
            'client_secret' => 'testing-facebook-secret',
            'redirect' => 'http://localhost/auth/facebook/callback',
        ]);
        $app['config']->set('services.github', [
            'client_id' => 'testing-github-id',
            'client_secret' => 'testing-github-secret',
            'redirect' => 'http://localhost/auth/github/callback',
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/reset-password/{token}', fn () => 'reset')->name('password.reset');
        $router->get('/home', fn () => 'home')->name('home');
        $router->get('/register/complete', fn () => 'complete')->name('register.complete');
        $router->get('/login', fn () => 'login')->name('login');
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable()->index();
            $table->timestamp('banned_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
        });

        Schema::create('auth_kit_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32);
            $table->string('destination');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['channel', 'destination']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    protected function createUser(array $attributes = []): TestUser
    {
        $attributes = array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
        ], $attributes);

        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make((string) $attributes['password']);
        }

        return TestUser::query()->create($attributes);
    }
}
