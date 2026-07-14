<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Exceptions;

use RuntimeException;

final class DriverNotFoundException extends RuntimeException
{
    public static function make(string $name): self
    {
        return new self("Auth driver [{$name}] is not registered.");
    }
}
