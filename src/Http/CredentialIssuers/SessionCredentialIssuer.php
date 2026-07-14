<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\CredentialIssuers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Technobase\AuthKit\Contracts\Credentials\CredentialIssuer;

final class SessionCredentialIssuer implements CredentialIssuer
{
    public function __construct(
        private readonly string $guard,
        private readonly string $homeRoute,
    ) {}

    public function issue(
        Authenticatable $subject,
        Request $request,
    ): RedirectResponse {
        Auth::guard($this->guard)->login($subject, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route($this->homeRoute));
    }
}
