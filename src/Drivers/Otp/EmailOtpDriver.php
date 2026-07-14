<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers\Otp;

use Illuminate\Http\Request;
use Technobase\AuthKit\Contracts\Drivers\AuthDriver;
use Technobase\AuthKit\Otp\OtpManager;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

final class EmailOtpDriver implements AuthDriver
{
    public function __construct(
        private readonly OtpManager $otp,
    ) {}

    public function name(): string
    {
        return 'email_otp';
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        return $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity
    {
        $email = strtolower(trim((string) $payload['email']));
        $code = (string) $payload['code'];

        $this->otp->verify('email', $email, $code);

        return new NormalizedIdentity(
            provider: 'email_otp',
            providerId: $email,
            email: $email,
            name: null,
            avatar: null,
        );
    }
}
