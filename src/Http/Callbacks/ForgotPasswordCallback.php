<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Callbacks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;

final class ForgotPasswordCallback
{
    public function __invoke(ForgotPasswordRequest $request, string $flavor = 'api'): JsonResponse|RedirectResponse
    {
        Password::broker($this->passwordBroker())->sendResetLink(
            $request->only('email'),
        );

        if ($flavor === 'web') {
            return back()->with('status', __('auth-kit::auth-kit.reset_sent'));
        }

        return response()->json([
            'message' => __('auth-kit::auth-kit.reset_sent'),
        ]);
    }

    private function passwordBroker(): string
    {
        return (string) config('auth-kit.password_reset.broker', 'users');
    }
}
