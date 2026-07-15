<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Technobase\AuthKit\Http\Controllers\Api\AuthController;
use Technobase\AuthKit\Support\Registry\AuthKitDrivers;

$api = (array) config('auth-kit.routes.api', []);
$drivers = AuthKitDrivers::enabled('api');

$otpDrivers = ['phone_otp'];
$socialDrivers = array_values(array_filter(
    $drivers,
    static fn (string $driver): bool => ! in_array($driver, $otpDrivers, true),
));

Route::middleware($api['middleware'] ?? ['api'])
    ->prefix(trim('api/'.($api['prefix'] ?? 'auth'), '/'))
    ->name('auth-kit.api.')
    ->group(function () use ($drivers, $socialDrivers): void {
        Route::get('providers', [AuthController::class, 'providers'])->name('providers');

        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('logout');

        foreach ($socialDrivers as $driver) {
            Route::post($driver, [AuthController::class, 'social'])
                ->defaults('driver', $driver)
                ->name("{$driver}");
        }

        if (in_array('phone_otp', $drivers, true)) {
            Route::post('otp/phone/send', [AuthController::class, 'sendPhoneOtp'])->name('otp.phone.send');
            Route::post('otp/phone/verify', [AuthController::class, 'verifyPhoneOtp'])->name('otp.phone.verify');
        }
    });
