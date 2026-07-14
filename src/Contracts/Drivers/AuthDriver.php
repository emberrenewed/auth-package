<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Contracts\Drivers;

use Illuminate\Http\Request;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

interface AuthDriver
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolveIdentity(array $payload): NormalizedIdentity;
}
