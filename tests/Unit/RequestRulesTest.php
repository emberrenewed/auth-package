<?php

declare(strict_types=1);

use Technobase\AuthKit\Http\Requests\LoginRequest;
use Technobase\AuthKit\Http\Requests\SocialLoginRequest;

it('authorizes login request and defines credential rules', function (): void {
    $request = LoginRequest::create('/api/auth/login', 'POST', [
        'email' => 'user@example.com',
        'password' => 'password',
        'remember' => true,
    ]);

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toHaveKeys(['email', 'password', 'remember']);
});

it('authorizes social login request and requires access_token', function (): void {
    $request = SocialLoginRequest::create('/api/auth/google', 'POST', [
        'access_token' => 'token',
    ]);

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toHaveKey('access_token');
});
