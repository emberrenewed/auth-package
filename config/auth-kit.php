<?php

declare(strict_types=1);

use Technobase\AuthKit\Otp\Channels\IraqiSmsChannel;
use Technobase\AuthKit\Support\Subjects\EloquentSubjectResolver;

return [
    'subjects' => [
        'web' => [
            'guard' => 'web',
            'model' => 'App\Models\User',
            'resolver' => EloquentSubjectResolver::class,
            'lookup_columns' => ['email'],
            'auto_create_on_social' => true,
            'home_route' => 'home',
            'registration_completion_route' => 'register.complete',
        ],
        'api' => [
            'guard' => 'sanctum',
            'model' => 'App\Models\User',
            'resolver' => EloquentSubjectResolver::class,
            'lookup_columns' => ['email', 'phone'],
            'auto_create_on_social' => false,
        ],
    ],

    'routes' => [
        'enabled' => true,

        'web' => [
            'prefix' => 'auth',
            'middleware' => ['web'],
        ],
        'api' => [
            'prefix' => 'auth',
            'middleware' => ['api'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled drivers
    |--------------------------------------------------------------------------
    |
    | This package supports:
    | - google
    | - facebook
    | - phone_otp (Iraqi SMS: Asiacell 077/078, Korek 075, Zain 079)
    |
    | Legacy list form is still supported: ['google', 'facebook'].
    |
    */
    'drivers' => [
        'web' => [
            'google' => true,
            'facebook' => true,
        ],
        'api' => [
            'google' => true,
            'facebook' => true,
            'phone_otp' => true,
        ],
    ],

    'otp' => [
        'length' => 6,
        'ttl_seconds' => 300,
        'max_attempts' => 5,
        'channels' => [
            'sms' => IraqiSmsChannel::class,
        ],
    ],
];
