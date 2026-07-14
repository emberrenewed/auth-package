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
            ->subject('Your login code')
            ->line('Your one-time login code is '.$this->code)
            ->line('This code expires shortly. Do not share it with anyone.');
    }
}
