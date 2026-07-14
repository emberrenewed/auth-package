<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers;

final class GoogleDriver extends AbstractSocialiteDriver
{
    public function name(): string
    {
        return 'google';
    }
}
