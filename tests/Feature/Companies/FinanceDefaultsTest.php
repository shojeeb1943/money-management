<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;

test('a new company gets default BD wallets and categories', function (): void {
    $user = User::factory()->create();

    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    expect(Wallet::query()->pluck('name')->sort()->values()->all())->toBe(['Bank', 'Card', 'Cash', 'Mobile Wallet'])
        ->and(Category::query()->where('kind', 'income')->count())->toBeGreaterThanOrEqual(2)
        ->and(Category::query()->where('kind', 'expense')->count())->toBeGreaterThanOrEqual(3);
});

test('the installed admin company gets defaults too', function (): void {
    $this->artisan('moneta:install', [
        '--name' => 'Test User',
        '--email' => 'test@example.com',
        '--password' => 'super-secret',
        '--company' => 'My Company',
    ])->assertSuccessful();

    expect(Wallet::query()->count())->toBe(4);
});
