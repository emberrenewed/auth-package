<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Contracts\Subjects;

use Illuminate\Contracts\Auth\Authenticatable;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

interface SubjectResolver
{
    public function resolve(NormalizedIdentity $identity, string $driver): ?Authenticatable;
}
