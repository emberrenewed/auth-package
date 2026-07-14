<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Drivers;

final class GithubDriver extends AbstractSocialiteDriver
{
    public function name(): string
    {
        return 'github';
    }
}
