<?php

declare(strict_types=1);

test('responses include baseline security headers', function (): void {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
