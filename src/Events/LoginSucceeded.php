<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LoginSucceeded
{
    use Dispatchable;

    public function __construct(
        public Authenticatable $subject,
        public string $driver,
        public string $flavor,
    ) {}
}
