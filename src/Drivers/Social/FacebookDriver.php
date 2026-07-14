<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers\Social;

final class FacebookDriver extends AbstractSocialiteDriver
{
    public function name(): string
    {
        return 'facebook';
    }
}
