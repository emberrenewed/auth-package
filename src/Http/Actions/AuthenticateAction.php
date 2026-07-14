<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Technobase\AuthKit\Events\LoginAttempted;
use Technobase\AuthKit\Events\LoginFailed;
use Technobase\AuthKit\Events\LoginSucceeded;
use Technobase\AuthKit\Events\SocialUserResolved;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;
use Technobase\AuthKit\Exceptions\InvalidCredentialsException;
use Technobase\AuthKit\Http\Controllers\Concerns\InteractsWithAuthKit;
use Technobase\AuthKit\Http\CredentialIssuers\SanctumCredentialIssuer;
use Technobase\AuthKit\Http\CredentialIssuers\SessionCredentialIssuer;

final class AuthenticateAction
{
    use InteractsWithAuthKit;

    private string $currentFlavor = 'api';

    public function __invoke(
        Request $request,
        string $driverName,
        bool $social = false,
        string $flavor = 'api',
    ): HttpResponse {
        $this->currentFlavor = $flavor;

        try {
            $driver = $this->resolveDriver($driverName);
        } catch (DriverNotFoundException) {
            return $this->failure($request, 'These credentials do not match our records.', 404, 'driver');
        }

        $payload = $driver->validate($request);

        LoginAttempted::dispatch($driverName, $request);

        try {
            $identity = $driver->resolveIdentity($payload);
        } catch (InvalidCredentialsException $exception) {
            LoginFailed::dispatch($driverName, $exception->reason, $request);

            return $this->failure($request, $this->failureMessage($exception->reason), 401);
        }

        $subject = $this->subjectResolver()->resolve($identity, $driverName);

        if ($subject === null) {
            LoginFailed::dispatch($driverName, 'subject_not_found', $request);

            if ($social && $this->isWeb()) {
                $request->session()->flash('auth_kit.pending_identity', $identity);

                return redirect()->route($this->registrationCompletionRoute());
            }

            return $this->failure($request, 'No account is linked to this identity.', 404);
        }

        if ($this->isBanned($subject)) {
            LoginFailed::dispatch($driverName, 'banned', $request);

            return $this->failure($request, 'This account has been suspended.', 403);
        }

        if ($social) {
            SocialUserResolved::dispatch($subject, $identity);
        }

        $response = $this->issueCredentials($subject, $request);

        LoginSucceeded::dispatch($subject, $driverName, $flavor);

        return $response;
    }

    private function issueCredentials(Authenticatable $subject, Request $request): HttpResponse
    {
        if ($this->isWeb()) {
            return (new SessionCredentialIssuer($this->guardName(), $this->homeRoute()))
                ->issue($subject, $request);
        }

        return (new SanctumCredentialIssuer)->issue($subject, $request);
    }

    private function failure(
        Request $request,
        string $message,
        int $status,
        string $field = 'email',
    ): HttpResponse {
        if ($this->isWeb()) {
            return back()
                ->withErrors([$field => $message])
                ->withInput($request->only('email', 'remember'));
        }

        return response()->json(['message' => $message], $status);
    }

    private function isWeb(): bool
    {
        return $this->currentFlavor === 'web';
    }

    private function guardName(): string
    {
        return (string) config('auth-kit.subjects.web.guard', 'web');
    }

    private function homeRoute(): string
    {
        return (string) config('auth-kit.subjects.web.home_route', 'home');
    }

    private function registrationCompletionRoute(): string
    {
        return (string) config('auth-kit.subjects.web.registration_completion_route', 'register.complete');
    }

    protected function flavor(): string
    {
        return $this->currentFlavor;
    }
}
