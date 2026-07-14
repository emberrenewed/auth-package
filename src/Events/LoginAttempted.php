<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

final readonly class LoginAttempted
{
    use Dispatchable;

    public function __construct(
        public string $driver,
        public Request $request,
    ) {}
}
