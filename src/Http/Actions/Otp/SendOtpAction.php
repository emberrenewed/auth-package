<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions\Otp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technobase\AuthKit\Otp\OtpManager;

final class SendOtpAction
{
    public function __construct(
        private readonly OtpManager $otp,
    ) {}

    public function __invoke(Request $request, string $channel): JsonResponse
    {
        $rules = match ($channel) {
            'email' => ['email' => ['required', 'email']],
            'whatsapp' => ['phone' => ['required', 'string']],
            default => [],
        };

        $payload = $request->validate($rules);

        $destination = match ($channel) {
            'email' => (string) $payload['email'],
            'whatsapp' => (string) $payload['phone'],
            default => '',
        };

        $this->otp->send($channel, $destination);

        return response()->json([
            'message' => 'If that destination is valid, we sent a code.',
        ]);
    }
}
