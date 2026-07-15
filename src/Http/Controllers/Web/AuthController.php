<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Actions\Auth\AuthenticateAction;
use Technobase\AuthKit\Http\Actions\Auth\LogoutAction;
use Technobase\AuthKit\Http\Actions\Social\RedirectToProviderAction;

final class AuthController extends Controller
{
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
}
