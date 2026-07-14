<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Identity;

final readonly class NormalizedIdentity
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $provider,
        public string $providerId,
        public ?string $email,
        public ?string $name,
        public ?string $avatar,
        public ?string $phone = null,
        public array $raw = [],
    ) {}
}
