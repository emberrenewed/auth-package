<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Technobase\AuthKit\Support\Phone\IraqiMobile;

final class IraqiMobileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || IraqiMobile::tryParse($value) === null) {
            $fail('Enter a valid Iraqi mobile number (Asiacell, Korek Telecom, or Zain Iraq).');
        }
    }
}
