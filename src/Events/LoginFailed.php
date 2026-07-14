<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

final readonly class LoginFailed
{
    use Dispatchable;

    public function __construct(
        public string $driver,
        public string $reason,
        public Request $request,
    ) {}
}
