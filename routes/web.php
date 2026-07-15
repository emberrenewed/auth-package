<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Technobase\AuthKit\Http\Controllers\Web\AuthController;
use Technobase\AuthKit\Support\Registry\AuthKitDrivers;

$web = (array) config('auth-kit.routes.web', []);
$drivers = AuthKitDrivers::enabled('web');
$otpDrivers = ['phone_otp'];
$socialDrivers = array_values(array_filter(
    $drivers,
    static fn (string $driver): bool => ! in_array($driver, $otpDrivers, true),
));

Route::middleware($web['middleware'] ?? ['web'])
    ->prefix($web['prefix'] ?? 'auth')
    ->name('auth-kit.web.')
    ->group(function () use ($socialDrivers): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        foreach ($socialDrivers as $driver) {
            Route::get("{$driver}/redirect", [AuthController::class, 'redirect'])
                ->defaults('driver', $driver)
                ->name("{$driver}.redirect");

            Route::get("{$driver}/callback", [AuthController::class, 'callback'])
                ->defaults('driver', $driver)
                ->name("{$driver}.callback");
        }
    });
