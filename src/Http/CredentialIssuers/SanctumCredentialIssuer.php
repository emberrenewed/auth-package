<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\CredentialIssuers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Technobase\AuthKit\Contracts\Credentials\CredentialIssuer;
use Technobase\AuthKit\Http\Resources\AuthResource;

final class SanctumCredentialIssuer implements CredentialIssuer
{
    public function issue(
        Authenticatable $subject,
        Request $request,
    ): JsonResponse {
        if (! is_callable([$subject, 'createToken'])) {
            throw new RuntimeException('Subject must support Sanctum createToken().');
        }

        /** @var list<string> $abilities */
        $abilities = config('auth-kit.sanctum_abilities', ['*']);

        $token = $subject->createToken('auth-kit', $abilities);

        return response()->json([
            'data' => ['user' => new AuthResource($subject)],
            'token' => $token->plainTextToken,
        ]);
    }
}
