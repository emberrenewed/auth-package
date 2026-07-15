<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Otp\Channels;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Technobase\AuthKit\Contracts\Otp\OtpChannel;
use Technobase\AuthKit\Support\Phone\IraqiMobile;

/**
 * Sends SMS OTPs to Iraqi mobiles (Asiacell / Korek / Zain) via a configurable HTTP gateway.
 *
 * Payload posted to services.iraqi_sms.endpoint:
 * {
 *   "to": "+9647XXXXXXXX",
 *   "digits": "9647XXXXXXXX",
 *   "carrier": "asiacell|korek|zain",
 *   "message": "Your code is: 123456",
 *   "code": "123456"
 * }
 */
final class IraqiSmsChannel implements OtpChannel
{
    public function send(string $destination, string $code): void
    {
        $mobile = IraqiMobile::parse($destination);
        $endpoint = (string) config('services.iraqi_sms.endpoint', '');
        $token = (string) config('services.iraqi_sms.token', '');

        if ($endpoint === '') {
            if (app()->environment('local', 'testing')) {
                app(LogOtpChannel::class)->send($mobile->digits, $code);

                return;
            }

            throw new RuntimeException('Iraqi SMS gateway is not configured (services.iraqi_sms.endpoint).');
        }

        $message = sprintf(
            (string) config('services.iraqi_sms.message_template', 'Your Auth Kit verification code is: %s'),
            $code,
        );

        $request = Http::acceptJson()->asJson();

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($endpoint, [
            'to' => $mobile->e164(),
            'digits' => $mobile->digits,
            'carrier' => $mobile->carrier->value,
            'carrier_label' => $mobile->carrier->label(),
            'message' => $message,
            'code' => $code,
            'from' => config('services.iraqi_sms.from'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Iraqi SMS OTP send failed: '.$response->body());
        }
    }
}
