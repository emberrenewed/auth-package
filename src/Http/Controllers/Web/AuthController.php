<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Callbacks\AuthenticateCallback;
use Technobase\AuthKit\Http\Callbacks\ForgotPasswordCallback;
use Technobase\AuthKit\Http\Callbacks\LogoutCallback;
use Technobase\AuthKit\Http\Callbacks\RedirectCallback;
use Technobase\AuthKit\Http\Callbacks\ResetPasswordCallback;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;
use Technobase\AuthKit\Http\Requests\ResetPasswordRequest;

final class AuthController extends Controller
{
    public function login(Request $request, AuthenticateCallback $authenticate): HttpResponse
    {
        return $authenticate($request, 'password', social: false, flavor: 'web');
    }

    public function redirect(string $driver, RedirectCallback $redirect): RedirectResponse
    {
        return $redirect($driver);
    }

    public function callback(Request $request, AuthenticateCallback $authenticate, string $driver): HttpResponse
    {
        return $authenticate($request, $driver, social: true, flavor: 'web');
    }

    public function logout(Request $request, LogoutCallback $logout): RedirectResponse
    {
        return $logout($request, flavor: 'web');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordCallback $forgotPassword): RedirectResponse
    {
        return $forgotPassword($request, flavor: 'web');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordCallback $resetPassword): RedirectResponse
    {
        return $resetPassword($request, flavor: 'web');
    }
}
