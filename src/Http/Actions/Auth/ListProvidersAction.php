<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Actions\Auth;

use Illuminate\Http\JsonResponse;
use Technobase\AuthKit\Facades\AuthKit;
use Technobase\AuthKit\Support\Registry\AuthKitDrivers;

final class ListProvidersAction
{
    public function __invoke(): JsonResponse
    {
        $enabled = AuthKitDrivers::enabled('api');
        $registered = AuthKit::drivers();

        return response()->json([
            'data' => array_values(array_intersect($registered, $enabled)),
        ]);
    }
}
