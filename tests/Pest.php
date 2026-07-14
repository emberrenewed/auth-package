<?php

declare(strict_types=1);

use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Mockery\MockInterface;
use Technobase\AuthKit\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function mockSocialUser(string $id, string $email, string $name): SocialiteUserContract
{
    /** @var SocialiteUserContract&MockInterface $user */
    $user = Mockery::mock(SocialiteUserContract::class);
    $user->shouldReceive('getId')->andReturn($id);
    $user->shouldReceive('getEmail')->andReturn($email);
    $user->shouldReceive('getName')->andReturn($name);
    $user->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

    return $user;
}

function mockSocialToken(string $driver, string $token, SocialiteUserContract $socialUser): void
{
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with($token)->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
}
