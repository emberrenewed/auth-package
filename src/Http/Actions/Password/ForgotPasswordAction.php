<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions\Password;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;

final class ForgotPasswordAction
{
    public function __invoke(ForgotPasswordRequest $request, string $flavor = 'api'): JsonResponse|RedirectResponse
    {
        Password::broker($this->passwordBroker())->sendResetLink(
            $request->only('email'),
        );

        if ($flavor === 'web') {
            return back()->with('status', 'If that email is in our system, we sent a link.');
        }

        return response()->json([
            'message' => 'If that email is in our system, we sent a link.',
        ]);
    }

    private function passwordBroker(): string
    {
        return (string) config('auth-kit.password_reset.broker', 'users');
    }
}
