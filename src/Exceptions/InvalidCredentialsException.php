<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Exceptions;

use Exception;
use Throwable;

final class InvalidCredentialsException extends Exception
{
    public function __construct(
        public string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($reason, 0, $previous);
    }
}
