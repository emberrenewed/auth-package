<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LoggedOut
{
    use Dispatchable;

    public function __construct(
        public Authenticatable $subject,
        public string $flavor,
    ) {}
}
