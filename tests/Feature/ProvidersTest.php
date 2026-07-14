<?php

declare(strict_types=1);

it('lists enabled api providers', function (): void {
    $response = $this->getJson('/api/auth/providers');

    $response->assertOk();

    expect($response->json('data'))->toEqual([
        'password',
        'google',
        'facebook',
        'github',
        'email_otp',
        'whatsapp_otp',
    ]);
});
