<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Phone;

use InvalidArgumentException;

/**
 * Normalizes and validates Iraqi mobile numbers for Asiacell, Korek, and Zain.
 *
 * Accepted inputs (examples):
 * - 07501234567          (11 digits, local)
 * - +9647501234567       (E.164)
 * - 9647501234567
 * - 7501234567           (10-digit national)
 *
 * Canonical storage form: 9647XXXXXXXXX (13 digits)
 */
final readonly class IraqiMobile
{
    private const COUNTRY_CODE = '964';

    public function __construct(
        public string $digits,
        public IraqiCarrier $carrier,
    ) {}

    public static function tryParse(string $input): ?self
    {
        try {
            return self::parse($input);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public static function parse(string $input): self
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('invalid_iraqi_mobile');
        }

        // 9640XXXXXXXXX → 964XXXXXXXXX
        if (str_starts_with($digits, self::COUNTRY_CODE.'0')) {
            $digits = self::COUNTRY_CODE.substr($digits, strlen(self::COUNTRY_CODE) + 1);
        }

        // Local 07XXXXXXXXX (11) → 9647XXXXXXXXX (13)
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE.substr($digits, 1);
        }

        // National 7XXXXXXXXX (10) → 9647XXXXXXXXX (13)
        if (strlen($digits) === 10 && str_starts_with($digits, '7')) {
            $digits = self::COUNTRY_CODE.$digits;
        }

        if (strlen($digits) !== 13 || ! str_starts_with($digits, self::COUNTRY_CODE.'7')) {
            throw new InvalidArgumentException('invalid_iraqi_mobile');
        }

        $prefix = substr($digits, 3, 2);
        $carrier = IraqiCarrier::fromPrefix($prefix);

        if ($carrier === null) {
            throw new InvalidArgumentException('invalid_iraqi_mobile');
        }

        $subscriber = substr($digits, 5);

        if (! ctype_digit($subscriber) || strlen($subscriber) !== 8) {
            throw new InvalidArgumentException('invalid_iraqi_mobile');
        }

        return new self($digits, $carrier);
    }

    public function local(): string
    {
        return '0'.substr($this->digits, 3);
    }

    public function e164(): string
    {
        return '+'.$this->digits;
    }

    public function __toString(): string
    {
        return $this->digits;
    }
}
