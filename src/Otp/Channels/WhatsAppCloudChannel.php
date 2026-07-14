<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Otp\Channels;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Technobase\AuthKit\Contracts\Otp\OtpChannel;

final class WhatsAppCloudChannel implements OtpChannel
{
    public function send(string $destination, string $code): void
    {
        $token = (string) config('services.whatsapp.token', '');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id', '');
        $template = (string) config('services.whatsapp.otp_template', 'auth_otp');

        if ($token === '' || $phoneNumberId === '') {
            if (app()->environment('local', 'testing')) {
                app(LogOtpChannel::class)->send($destination, $code);

                return;
            }

            throw new RuntimeException('WhatsApp Cloud API credentials are not configured.');
        }

        $phone = preg_replace('/\D+/', '', $destination) ?? $destination;

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'en_US'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp OTP send failed: '.$response->body());
        }
    }
}
