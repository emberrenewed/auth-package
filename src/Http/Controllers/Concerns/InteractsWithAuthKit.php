<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Technobase\AuthKit\Contracts\AuthDriver;
use Technobase\AuthKit\Contracts\SubjectResolver;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Support\DriverRegistry;

trait InteractsWithAuthKit
{
    abstract protected function flavor(): string;

    protected function resolveDriver(string $name): AuthDriver
    {
        app()->instance('auth-kit.flavor', $this->flavor());

        $allowed = config("auth-kit.drivers.{$this->flavor()}", []);

        if (! in_array($name, $allowed, true)) {
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
            'too_many_attempts' => __('auth-kit::auth-kit.throttle', [
                'seconds' => (int) config('auth-kit.throttle.decay_minutes', 1) * 60,
            ]),
            'social_failed',
            'google_authentication_failed',
            'facebook_authentication_failed',
            'github_authentication_failed',
            'otp_invalid',
            'otp_expired' => __('auth-kit::auth-kit.social_failed'),
            default => __('auth-kit::auth-kit.failed'),
        };
    }
}
