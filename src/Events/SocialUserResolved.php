<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Technobase\AuthKit\Support\NormalizedIdentity;

final readonly class SocialUserResolved
{
    use Dispatchable;

    public function __construct(
        public Authenticatable $subject,
        public NormalizedIdentity $identity,
    ) {}
}
