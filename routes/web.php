<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Technobase\AuthKit\Http\Controllers\Web\AuthController;
use Technobase\AuthKit\Support\AuthKitDrivers;

$web = (array) config('auth-kit.routes.web', []);
$drivers = AuthKitDrivers::enabled('web');
$otpDrivers = ['email_otp', 'whatsapp_otp'];
$socialDrivers = array_values(array_filter(
    $drivers,
    static fn (string $driver): bool => $driver !== 'password' && ! in_array($driver, $otpDrivers, true),
));

Route::middleware($web['middleware'] ?? ['web'])
    ->prefix($web['prefix'] ?? 'auth')
    ->name('auth-kit.web.')
    ->group(function () use ($drivers, $socialDrivers): void {
        if (in_array('password', $drivers, true)) {
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
            Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
        }

        foreach ($socialDrivers as $driver) {
            Route::get("{$driver}/redirect", [AuthController::class, 'redirect'])
                ->defaults('driver', $driver)
                ->name("{$driver}.redirect");
            Route::get("{$driver}/callback", [AuthController::class, 'callback'])
                ->defaults('driver', $driver)
                ->name("{$driver}.callback");
        }
    });
