<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Actions\Auth\AuthenticateAction;
use Technobase\AuthKit\Http\Actions\Auth\ListProvidersAction;
use Technobase\AuthKit\Http\Actions\Auth\LogoutAction;
use Technobase\AuthKit\Http\Actions\Otp\SendOtpAction;

final class AuthController extends Controller
{
    public function social(Request $request, AuthenticateAction $authenticate, string $driver): HttpResponse
    {
        return $authenticate($request, $driver, social: true, flavor: 'api');
    }

    public function sendPhoneOtp(Request $request, SendOtpAction $sendOtp): JsonResponse
    {
        return $sendOtp($request, 'sms');
    }

    public function verifyPhoneOtp(Request $request, AuthenticateAction $authenticate): HttpResponse
    {
        return $authenticate($request, 'phone_otp', social: false, flavor: 'api');
    }

    public function logout(Request $request, LogoutAction $logout): JsonResponse
    {
        return $logout($request, flavor: 'api');
    }

    public function providers(ListProvidersAction $providers): JsonResponse
    {
        return $providers();
    }
}
