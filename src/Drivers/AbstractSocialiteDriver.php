<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Technobase\AuthKit\Contracts\AuthDriver;
use Technobase\AuthKit\Exceptions\InvalidCredentialsException;
use Technobase\AuthKit\Support\NormalizedIdentity;
use Throwable;

abstract class AbstractSocialiteDriver implements AuthDriver
{
    public function __construct(
        private readonly string $flavor = 'api',
    ) {}

    abstract public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        if ($this->flavor === 'api') {
            return $request->validate([
                'access_token' => ['required', 'string'],
            ]);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity
    {
        $providerName = $this->name();

        try {
            $provider = Socialite::driver($providerName);

            if (! $provider instanceof AbstractProvider) {
                throw new InvalidCredentialsException("{$providerName}_authentication_failed");
            }

            /** @var SocialiteUser $socialUser */
            $socialUser = isset($payload['access_token'])
                ? $provider->stateless()->userFromToken((string) $payload['access_token'])
                : $provider->user();
        } catch (InvalidCredentialsException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidCredentialsException("{$providerName}_authentication_failed", $exception);
        }

        return new NormalizedIdentity(
            provider: $providerName,
            providerId: (string) $socialUser->getId(),
            email: $socialUser->getEmail(),
            name: $socialUser->getName(),
            avatar: $socialUser->getAvatar(),
            raw: (array) $socialUser,
        );
    }
}
