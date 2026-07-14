<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Technobase\AuthKit\Http\Controllers\Api\AuthController;

$api = (array) config('auth-kit.routes.api', []);
$drivers = (array) config('auth-kit.drivers.api', []);

$otpDrivers = ['email_otp', 'whatsapp_otp'];
$socialDrivers = array_values(array_filter(
    $drivers,
    static fn (string $driver): bool => $driver !== 'password' && ! in_array($driver, $otpDrivers, true),
));

Route::middleware($api['middleware'] ?? ['api'])
    ->prefix(trim('api/'.($api['prefix'] ?? 'auth'), '/'))
    ->name('auth-kit.api.')
    ->group(function () use ($drivers, $socialDrivers): void {
        if (in_array('password', $drivers, true)) {
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('logout', [AuthController::class, 'logout'])
                ->middleware('auth:sanctum')
                ->name('logout');
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
            Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
        }

        Route::get('providers', [AuthController::class, 'providers'])->name('providers');

        foreach ($socialDrivers as $driver) {
            Route::post($driver, [AuthController::class, 'social'])
                ->defaults('driver', $driver)
                ->name("{$driver}");
        }

        if (in_array('email_otp', $drivers, true)) {
            Route::post('otp/email/send', [AuthController::class, 'sendEmailOtp'])->name('otp.email.send');
            Route::post('otp/email/verify', [AuthController::class, 'verifyEmailOtp'])->name('otp.email.verify');
        }

        if (in_array('whatsapp_otp', $drivers, true)) {
            Route::post('otp/whatsapp/send', [AuthController::class, 'sendWhatsAppOtp'])->name('otp.whatsapp.send');
            Route::post('otp/whatsapp/verify', [AuthController::class, 'verifyWhatsAppOtp'])->name('otp.whatsapp.verify');
        }
    });
