<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Http\Controllers\Concerns\InteractsWithAuthKit;

final class RedirectToProviderAction
{
    use InteractsWithAuthKit;

    public function __invoke(string $driver): RedirectResponse
    {
        try {
            $this->resolveDriver($driver);
        } catch (DriverNotFoundException) {
            return back()->withErrors([
                'driver' => 'These credentials do not match our records.',
            ]);
        }

        $redirect = Socialite::driver($driver)->redirect();

        return $redirect instanceof RedirectResponse
            ? $redirect
            : redirect()->to($redirect->getTargetUrl());
    }

    protected function flavor(): string
    {
        return 'web';
    }
}
