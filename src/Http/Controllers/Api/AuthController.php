<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Http\Actions\Auth\AuthenticateAction;
use Technobase\AuthKit\Http\Actions\Password\ForgotPasswordAction;
use Technobase\AuthKit\Http\Actions\Auth\ListProvidersAction;
use Technobase\AuthKit\Http\Actions\Auth\LogoutAction;
use Technobase\AuthKit\Http\Actions\Password\ResetPasswordAction;
use Technobase\AuthKit\Http\Actions\Otp\SendOtpAction;
use Technobase\AuthKit\Http\Requests\ForgotPasswordRequest;
use Technobase\AuthKit\Http\Requests\ResetPasswordRequest;

final class AuthController extends Controller
{
    public function login(Request $request, AuthenticateAction $authenticate, string $driver = 'password'): HttpResponse
    {
        return $authenticate($request, $driver, social: false, flavor: 'api');
    }

    public function social(Request $request, AuthenticateAction $authenticate, string $driver): HttpResponse
    {
        return $authenticate($request, $driver, social: true, flavor: 'api');
    }

    public function sendEmailOtp(Request $request, SendOtpAction $sendOtp): JsonResponse
    {
        return $sendOtp($request, 'email');
    }

    public function verifyEmailOtp(Request $request, AuthenticateAction $authenticate): HttpResponse
    {
        return $authenticate($request, 'email_otp', social: false, flavor: 'api');
    }

    public function sendWhatsAppOtp(Request $request, SendOtpAction $sendOtp): JsonResponse
    {
        return $sendOtp($request, 'whatsapp');
    }

    public function verifyWhatsAppOtp(Request $request, AuthenticateAction $authenticate): HttpResponse
    {
        return $authenticate($request, 'whatsapp_otp', social: false, flavor: 'api');
    }

    public function logout(Request $request, LogoutAction $logout): JsonResponse
    {
        return $logout($request, flavor: 'api');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $forgotPassword): JsonResponse
    {
        return $forgotPassword($request, flavor: 'api');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): JsonResponse
    {
        return $resetPassword($request, flavor: 'api');
    }

    public function providers(ListProvidersAction $providers): JsonResponse
    {
        return $providers();
    }
}
