<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Technobase\AuthKit\Contracts\AuthDriver;
use Technobase\AuthKit\Contracts\SubjectResolver;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Support\AuthKitDrivers;
use Technobase\AuthKit\Support\DriverRegistry;

trait InteractsWithAuthKit
{
    abstract protected function flavor(): string;

    protected function resolveDriver(string $name): AuthDriver
    {
        app()->instance('auth-kit.flavor', $this->flavor());

        if (! AuthKitDrivers::isEnabled($this->flavor(), $name)) {
            throw DriverNotFoundException::make($name);
        }

        return app(DriverRegistry::class)->driver($name);
    }

    protected function subjectResolver(): SubjectResolver
    {
        $config = (array) config("auth-kit.subjects.{$this->flavor()}", []);

        $resolverClass = $config['resolver'];

        return new $resolverClass($config);
    }

    protected function isBanned(Authenticatable $subject): bool
    {
        if (method_exists($subject, 'isBanned')) {
            return (bool) $subject->isBanned();
        }

        if ($subject instanceof Model) {
            return $subject->getAttribute('banned_at') !== null;
        }

        return false;
    }

    protected function failureMessage(string $reason): string
    {
        return match ($reason) {
            'too_many_attempts' => 'Too many login attempts. Try again in '
                .((int) config('auth-kit.throttle.decay_minutes', 1) * 60)
                .' s.',
            'social_failed',
            'google_authentication_failed',
            'facebook_authentication_failed',
            'github_authentication_failed',
            'otp_invalid',
            'otp_expired' => 'Social authentication failed. Please try again.',
            default => 'These credentials do not match our records.',
        };
    }
}
