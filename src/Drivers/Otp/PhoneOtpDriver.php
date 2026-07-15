<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers\Otp;

use Illuminate\Http\Request;
use Technobase\AuthKit\Contracts\Drivers\AuthDriver;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Rules\IraqiMobileRule;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;
use Technobase\AuthKit\Support\Phone\IraqiMobile;

final class PhoneOtpDriver implements AuthDriver
{
    public function __construct(
        private readonly OtpManager $otp,
    ) {}

    public function name(): string
    {
        return 'phone_otp';
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        return $request->validate([
            'phone' => ['required', 'string', new IraqiMobileRule],
            'code' => ['required', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity
    {
        $mobile = IraqiMobile::parse((string) $payload['phone']);
        $code = (string) $payload['code'];

        $this->otp->verify('sms', $mobile->digits, $code);

        return new NormalizedIdentity(
            provider: 'phone_otp',
            providerId: $mobile->digits,
            email: null,
            name: null,
            avatar: null,
            phone: $mobile->digits,
            raw: [
                'carrier' => $mobile->carrier->value,
                'carrier_label' => $mobile->carrier->label(),
            ],
        );
    }
}
