<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Callbacks\AuthenticateCallback;
use Technobase\AuthKit\Http\Callbacks\ForgotPasswordCallback;
use Technobase\AuthKit\Http\Callbacks\LogoutCallback;
use Technobase\AuthKit\Http\Callbacks\ProvidersCallback;
use Technobase\AuthKit\Http\Callbacks\ResetPasswordCallback;
use Technobase\AuthKit\Http\Callbacks\SendOtpCallback;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;
use Technobase\AuthKit\Http\Requests\ResetPasswordRequest;

final class AuthController extends Controller
{
    public function login(Request $request, AuthenticateCallback $authenticate, string $driver = 'password'): HttpResponse
    {
        return $authenticate($request, $driver, social: false, flavor: 'api');
    }

    public function social(Request $request, AuthenticateCallback $authenticate, string $driver): HttpResponse
    {
        return $authenticate($request, $driver, social: true, flavor: 'api');
    }

    public function sendEmailOtp(Request $request, SendOtpCallback $sendOtp): JsonResponse
    {
        return $sendOtp($request, 'email');
    }

    public function verifyEmailOtp(Request $request, AuthenticateCallback $authenticate): HttpResponse
    {
        return $authenticate($request, 'email_otp', social: false, flavor: 'api');
    }

    public function sendWhatsAppOtp(Request $request, SendOtpCallback $sendOtp): JsonResponse
    {
        return $sendOtp($request, 'whatsapp');
    }

    public function verifyWhatsAppOtp(Request $request, AuthenticateCallback $authenticate): HttpResponse
    {
        return $authenticate($request, 'whatsapp_otp', social: false, flavor: 'api');
    }

    public function logout(Request $request, LogoutCallback $logout): JsonResponse
    {
        return $logout($request, flavor: 'api');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordCallback $forgotPassword): JsonResponse
    {
        return $forgotPassword($request, flavor: 'api');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordCallback $resetPassword): JsonResponse
    {
        return $resetPassword($request, flavor: 'api');
    }

    public function providers(ProvidersCallback $providers): JsonResponse
    {
        return $providers();
    }
}
