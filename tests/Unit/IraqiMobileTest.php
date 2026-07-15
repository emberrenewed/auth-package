<?php

declare(strict_types=1);

use Technobase\AuthKit\Support\Phone\IraqiCarrier;
use Technobase\AuthKit\Support\Phone\IraqiMobile;

it('parses asiacell numbers', function (string $input): void {
    $mobile = IraqiMobile::parse($input);

    expect($mobile->digits)->toBe('9647701234567')
        ->and($mobile->carrier)->toBe(IraqiCarrier::Asiacell)
        ->and($mobile->local())->toBe('07701234567')
        ->and($mobile->e164())->toBe('+9647701234567');
})->with([
    '07701234567',
    '+9647701234567',
    '9647701234567',
    '7701234567',
]);

it('parses korek numbers', function (): void {
    $mobile = IraqiMobile::parse('07501234567');

    expect($mobile->carrier)->toBe(IraqiCarrier::Korek)
        ->and($mobile->digits)->toBe('9647501234567');
});

it('parses zain iraq numbers', function (): void {
    $mobile = IraqiMobile::parse('+9647901234567');

    expect($mobile->carrier)->toBe(IraqiCarrier::Zain)
        ->and($mobile->digits)->toBe('9647901234567');
});

it('rejects non-iraqi and unknown prefixes', function (string $input): void {
    expect(IraqiMobile::tryParse($input))->toBeNull();
})->with([
    '+15551234567',
    '07101234567',
    '07601234567',
    '123',
    '',
]);
