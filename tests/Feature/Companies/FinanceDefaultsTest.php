<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\User;

test('a new company gets default BD wallets and categories', function (): void {
    $user = User::factory()->create();

    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    expect($company->wallets()->pluck('name')->sort()->values()->all())->toBe(['Bank', 'Card', 'Cash', 'Mobile Wallet'])
        ->and($company->categories()->where('kind', 'income')->count())->toBeGreaterThanOrEqual(2)
        ->and($company->categories()->where('kind', 'expense')->count())->toBeGreaterThanOrEqual(3);
});

test('the installed admin company gets defaults too', function (): void {
    $this->artisan('moneta:install', [
        '--name' => 'Test User',
        '--email' => 'test@example.com',
        '--password' => 'super-secret',
        '--company' => 'My Company',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->currentCompany->wallets()->count())->toBe(4);
});
