<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Technobase\AuthKit\Events\LoggedOut;

final class LogoutAction
{
    public function __invoke(Request $request, string $flavor = 'api'): JsonResponse|RedirectResponse
    {
        /** @var Authenticatable|null $subject */
        $subject = $flavor === 'web'
            ? Auth::guard($this->guardName())->user()
            : $request->user();

        if ($flavor === 'web') {
            Auth::guard($this->guardName())->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($subject !== null) {
                LoggedOut::dispatch($subject, $flavor);
            }

            return redirect('/')->with('status', 'You have been logged out successfully.');
        }

        if (is_callable([$subject, 'currentAccessToken'])) {
            $accessToken = $subject->currentAccessToken();

            if ($accessToken instanceof Model) {
                $accessToken->delete();
            }
        }

        if ($subject !== null) {
            LoggedOut::dispatch($subject, $flavor);
        }

        return response()->json([
            'message' => 'You have been logged out successfully.',
        ]);
    }

    private function guardName(): string
    {
        return (string) config('auth-kit.subjects.web.guard', 'web');
    }
}
