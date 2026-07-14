<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Technobase\AuthKit\Events\Auth\LoggedOut;
use Technobase\AuthKit\Tests\TestCase;
use Technobase\AuthKit\Tests\TestUser;

it('revokes current sanctum token on api logout', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();
    $token = $user->createToken('auth-kit');

    expect($user->tokens()->count())->toBe(1);

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout');

    $response->assertOk()->assertJson([
        'message' => 'You have been logged out successfully.',
    ]);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('fires LoggedOut event with correct subject and flavor', function (): void {
    /** @var TestCase $this */
    Event::fake([LoggedOut::class]);

    $user = $this->createUser();
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/logout')->assertOk();

    Event::assertDispatched(LoggedOut::class, function (LoggedOut $event) use ($user): bool {
        return $event->subject instanceof TestUser
            && $event->subject->is($user)
            && $event->flavor === 'api';
    });
});
