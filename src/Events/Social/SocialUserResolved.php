<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Events\Social;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

final readonly class SocialUserResolved
{
    use Dispatchable;

    public function __construct(
        public Authenticatable $subject,
        public NormalizedIdentity $identity,
    ) {}
}
