<?php

declare(strict_types=1);

use Technobase\AuthKit\Otp\Channels\MailOtpChannel;
use Technobase\AuthKit\Otp\Channels\WhatsAppCloudChannel;
use Technobase\AuthKit\Support\EloquentSubjectResolver;

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
    | Set a driver to true to enable it, false to disable it. Package consumers
    | can keep only the providers they need (e.g. Google only, Facebook only).
    |
    | Legacy list form is still supported: ['password', 'google'].
    |
    */
    'drivers' => [
        'web' => [
            'password' => true,
            'google' => true,
            'facebook' => true,
            'github' => true,
        ],
        'api' => [
            'password' => true,
            'google' => true,
            'facebook' => true,
            'github' => true,
            'email_otp' => true,
            'whatsapp_otp' => true,
        ],
    ],

    'throttle' => [
        'max_attempts' => 5,
        'decay_minutes' => 1,
    ],

    'password_reset' => [
        'token_lifetime_seconds' => 30,
        'broker' => 'users',
    ],

    'otp' => [
        'length' => 6,
        'ttl_seconds' => 300,
        'max_attempts' => 5,
        'channels' => [
            'email' => MailOtpChannel::class,
            'whatsapp' => WhatsAppCloudChannel::class,
        ],
    ],
];
