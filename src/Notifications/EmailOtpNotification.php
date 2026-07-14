<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmailOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth-kit::auth-kit.otp_mail_subject'))
            ->line(__('auth-kit::auth-kit.otp_mail_line', ['code' => $this->code]))
            ->line(__('auth-kit::auth-kit.otp_mail_expire'));
    }
}
