<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions;

use Illuminate\Http\JsonResponse;
use Technobase\AuthKit\Facades\AuthKit;

final class ListProvidersAction
{
    public function __invoke(): JsonResponse
    {
        $enabled = (array) config('auth-kit.drivers.api', []);
        $registered = AuthKit::drivers();

        return response()->json([
            'data' => array_values(array_intersect($registered, $enabled)),
        ]);
    }
}
