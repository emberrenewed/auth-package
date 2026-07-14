<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Registry;

/**
 * Resolves which auth drivers are enabled for a flavor (api / web).
 *
 * Supports two config shapes:
 *
 * 1. Boolean map (preferred):
 *    ['password' => true, 'google' => true, 'facebook' => false]
 *
 * 2. Flat list (legacy):
 *    ['password', 'google']
 */
final class AuthKitDrivers
{
    /**
     * @return list<string>
     */
    public static function enabled(string $flavor): array
    {
        $drivers = (array) config("auth-kit.drivers.{$flavor}", []);

        if ($drivers === []) {
            return [];
        }

        if (! array_is_list($drivers)) {
            return array_values(array_keys(array_filter(
                $drivers,
                static fn (mixed $enabled): bool => (bool) $enabled,
            )));
        }

        return array_values(array_map('strval', $drivers));
    }

    public static function isEnabled(string $flavor, string $driver): bool
    {
        return in_array($driver, self::enabled($flavor), true);
    }
}
