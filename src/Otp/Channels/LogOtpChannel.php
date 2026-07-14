<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Otp\Channels;

use Illuminate\Support\Facades\Log;
use Technobase\AuthKit\Contracts\Otp\OtpChannel;

final class LogOtpChannel implements OtpChannel
{
    public function send(string $destination, string $code): void
    {
        Log::info('auth-kit.otp', [
            'destination' => $destination,
            'code' => $code,
        ]);
    }
}
