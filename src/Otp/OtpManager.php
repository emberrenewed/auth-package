<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Otp;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Technobase\AuthKit\Contracts\OtpChannel;
use Technobase\AuthKit\Exceptions\InvalidCredentialsException;

final class OtpManager
{
    public function send(string $channel, string $destination): void
    {
        $destination = $this->normalizeDestination($channel, $destination);
        $code = $this->generateCode();

        DB::table('auth_kit_otps')->updateOrInsert(
            [
                'channel' => $channel,
                'destination' => $destination,
            ],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addSeconds((int) config('auth-kit.otp.ttl_seconds', 300)),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->channel($channel)->send($destination, $code);
    }

    public function verify(string $channel, string $destination, string $code): bool
    {
        $destination = $this->normalizeDestination($channel, $destination);

        $row = DB::table('auth_kit_otps')
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->first();

        if ($row === null) {
            throw new InvalidCredentialsException('otp_invalid');
        }

        $maxAttempts = (int) config('auth-kit.otp.max_attempts', 5);

        if ((int) $row->attempts >= $maxAttempts) {
            throw new InvalidCredentialsException('otp_invalid');
        }

        if (Carbon::parse((string) $row->expires_at)->isPast()) {
            DB::table('auth_kit_otps')
                ->where('channel', $channel)
                ->where('destination', $destination)
                ->delete();

            throw new InvalidCredentialsException('otp_expired');
        }

        if (! Hash::check($code, (string) $row->code_hash)) {
            DB::table('auth_kit_otps')
                ->where('channel', $channel)
                ->where('destination', $destination)
                ->increment('attempts');

            throw new InvalidCredentialsException('otp_invalid');
        }

        DB::table('auth_kit_otps')
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->delete();

        return true;
    }

    private function channel(string $channel): OtpChannel
    {
        $map = (array) config('auth-kit.otp.channels', []);
        $class = $map[$channel] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidCredentialsException('otp_invalid');
        }

        /** @var OtpChannel $instance */
        $instance = app($class);

        return $instance;
    }

    private function generateCode(): string
    {
        $length = max(4, (int) config('auth-kit.otp.length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function normalizeDestination(string $channel, string $destination): string
    {
        $destination = trim($destination);

        if ($channel === 'whatsapp') {
            return preg_replace('/\D+/', '', $destination) ?? $destination;
        }

        return strtolower($destination);
    }
}
