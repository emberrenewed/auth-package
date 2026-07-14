<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface CredentialIssuer
{
    public function issue(
        Authenticatable $subject,
        Request $request,
    ): Response|JsonResponse|RedirectResponse;
}
