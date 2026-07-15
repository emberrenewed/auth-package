<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Phone;

enum IraqiCarrier: string
{
    case Asiacell = 'asiacell';
    case Korek = 'korek';
    case Zain = 'zain';

    /**
     * Mobile network prefixes after country code 964 (or local leading 0).
     *
     * @return list<string>
     */
    public function prefixes(): array
    {
        return match ($this) {
            self::Asiacell => ['77', '78'],
            self::Korek => ['75'],
            self::Zain => ['79'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Asiacell => 'Asiacell',
            self::Korek => 'Korek Telecom',
            self::Zain => 'Zain Iraq',
        };
    }

    public static function fromPrefix(string $prefix): ?self
    {
        foreach (self::cases() as $carrier) {
            if (in_array($prefix, $carrier->prefixes(), true)) {
                return $carrier;
            }
        }

        return null;
    }
}
