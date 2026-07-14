<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Facades;

use Illuminate\Support\Facades\Facade;
use Technobase\AuthKit\Support\DriverRegistry;

final class AuthKit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DriverRegistry::class;
    }
}
