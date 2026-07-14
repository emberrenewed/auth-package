<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Actions\AuthenticateAction;
use Technobase\AuthKit\Http\Actions\ForgotPasswordAction;
use Technobase\AuthKit\Http\Actions\LogoutAction;
use Technobase\AuthKit\Http\Actions\RedirectToProviderAction;
use Technobase\AuthKit\Http\Actions\ResetPasswordAction;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;
use Technobase\AuthKit\Http\Requests\ResetPasswordRequest;

final class AuthController extends Controller
{
    public function login(Request $request, AuthenticateAction $authenticate): HttpResponse
    {
        return $authenticate($request, 'password', social: false, flavor: 'web');
    }

    public function redirect(string $driver, RedirectToProviderAction $redirect): RedirectResponse
    {
        return $redirect($driver);
    }

    public function callback(Request $request, AuthenticateAction $authenticate, string $driver): HttpResponse
    {
        return $authenticate($request, $driver, social: true, flavor: 'web');
    }

    public function logout(Request $request, LogoutAction $logout): RedirectResponse
    {
        return $logout($request, flavor: 'web');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $forgotPassword): RedirectResponse
    {
        return $forgotPassword($request, flavor: 'web');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): RedirectResponse
    {
        return $resetPassword($request, flavor: 'web');
    }
}
