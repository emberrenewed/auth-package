<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Technobase\AuthKit\Contracts\AuthDriver;
use Technobase\AuthKit\Exceptions\InvalidCredentialsException;
use Technobase\AuthKit\Support\NormalizedIdentity;

final class PasswordDriver implements AuthDriver
{
    public function __construct(
        private readonly string $flavor,
        private readonly Request $request,
    ) {}

    public function name(): string
    {
        return 'password';
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        return $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity
    {
        $email = (string) $payload['email'];
        $key = 'auth_kit_login:'.md5($email.'|'.$this->request->ip());

        $maxAttempts = (int) config('auth-kit.throttle.max_attempts', 5);
        $decaySeconds = (int) config('auth-kit.throttle.decay_minutes', 1) * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new InvalidCredentialsException('too_many_attempts');
        }

        $guard = $this->guardName();

        $credentials = [
            'email' => $email,
            'password' => (string) $payload['password'],
        ];

        if (! Auth::guard($guard)->validate($credentials)) {
            RateLimiter::hit($key, $decaySeconds);

            throw new InvalidCredentialsException('invalid_credentials');
        }

        RateLimiter::clear($key);

        $providerName = (string) config("auth.guards.{$guard}.provider");
        $subject = Auth::createUserProvider($providerName)?->retrieveByCredentials([
            'email' => $email,
        ]);

        if (! $subject instanceof Model) {
            throw new InvalidCredentialsException('invalid_credentials');
        }

        $firstName = $subject->getAttribute('first_name');
        $lastName = $subject->getAttribute('last_name');
        $name = trim(($firstName ?? '').' '.($lastName ?? ''));

        return new NormalizedIdentity(
            provider: 'password',
            providerId: (string) $subject->getAuthIdentifier(),
            email: $email,
            name: $name !== '' ? $name : null,
            avatar: $subject->getAttribute('avatar'),
        );
    }

    private function guardName(): string
    {
        $guard = (string) config("auth-kit.subjects.{$this->flavor}.guard", 'web');

        if ($guard === 'sanctum') {
            return (string) config('auth-kit.subjects.web.guard', 'web');
        }

        return $guard;
    }
}
