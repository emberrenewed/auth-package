<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers;

use Illuminate\Http\Request;
use Technobase\AuthKit\Contracts\AuthDriver;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Support\NormalizedIdentity;

final class WhatsAppOtpDriver implements AuthDriver
{
    public function __construct(
        private readonly OtpManager $otp,
    ) {}

    public function name(): string
    {
        return 'whatsapp_otp';
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        return $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity
    {
        $phone = preg_replace('/\D+/', '', (string) $payload['phone']) ?? (string) $payload['phone'];
        $code = (string) $payload['code'];

        $this->otp->verify('whatsapp', $phone, $code);

        return new NormalizedIdentity(
            provider: 'whatsapp_otp',
            providerId: $phone,
            email: null,
            name: null,
            avatar: null,
            phone: $phone,
        );
    }
}
