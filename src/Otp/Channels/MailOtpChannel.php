<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Otp\Channels;

use Illuminate\Support\Facades\Notification as NotificationFacade;
use Technobase\AuthKit\Contracts\OtpChannel;
use Technobase\AuthKit\Notifications\EmailOtpNotification;

final class MailOtpChannel implements OtpChannel
{
    public function send(string $destination, string $code): void
    {
        NotificationFacade::route('mail', $destination)
            ->notify(new EmailOtpNotification($code));
    }
}
