<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Contracts\Otp;

interface OtpChannel
{
    public function send(string $destination, string $code): void;
}
