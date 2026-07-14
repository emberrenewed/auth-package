<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Technobase\AuthKit\Support\NormalizedIdentity;

interface SubjectResolver
{
    public function resolve(NormalizedIdentity $identity, string $driver): ?Authenticatable;
}
