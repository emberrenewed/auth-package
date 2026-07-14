<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions\Password;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Technobase\AuthKit\Http\Requests\ResetPasswordRequest;

final class ResetPasswordAction
{
    public function __invoke(ResetPasswordRequest $request, string $flavor = 'api'): JsonResponse|RedirectResponse
    {
        $status = Password::broker($this->passwordBroker())->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            if ($flavor === 'web') {
                return back()
                    ->withErrors(['email' => 'These credentials do not match our records.'])
                    ->withInput($request->only('email'));
            }

            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 422);
        }

        if ($flavor === 'web') {
            return back()->with('status', 'Your password has been reset successfully.');
        }

        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }

    private function passwordBroker(): string
    {
        return (string) config('auth-kit.password_reset.broker', 'users');
    }
}
