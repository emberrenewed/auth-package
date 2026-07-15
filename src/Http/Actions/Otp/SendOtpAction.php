<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions\Otp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Rules\IraqiMobileRule;
use Technobase\AuthKit\Support\Phone\IraqiMobile;

final class SendOtpAction
{
    public function __construct(
        private readonly OtpManager $otp,
    ) {}

    public function __invoke(Request $request, string $channel = 'sms'): JsonResponse
    {
        $payload = $request->validate([
            'phone' => ['required', 'string', new IraqiMobileRule],
        ]);

        $mobile = IraqiMobile::parse((string) $payload['phone']);

        $this->otp->send($channel, $mobile->digits);

        return response()->json([
            'message' => 'If that destination is valid, we sent a code.',
            'carrier' => $mobile->carrier->value,
            'carrier_label' => $mobile->carrier->label(),
        ]);
    }
}
