<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;

test('moneta:install with no options uses the default credentials', function (): void {
    $this->artisan('moneta:install')->assertSuccessful();

    $user = User::query()->where('email', 'admin@admin.com')->firstOrFail();

    expect($user->name)->toBe('Admin')
        ->and(Company::query()->where('name', 'Demo Company')->exists())->toBeTrue();

    $this->post(route('login.store'), [
        'email' => 'admin@admin.com',
        'password' => '12345678',
    ])->assertRedirect();
    $this->assertAuthenticated();
});

test('moneta:install creates the admin account and company', function (): void {
    $this->artisan('moneta:install', [
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
        '--password' => 'super-secret',
        '--company' => 'Acme Studio',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $company = Company::query()->where('name', 'Acme Studio')->firstOrFail();

    expect($user->currentCompany->is($company))->toBeTrue()
        ->and(Wallet::query()->count())->toBeGreaterThan(0);

    $this->post(route('login.store'), [
        'email' => 'admin@example.com',
        'password' => 'super-secret',
    ])->assertRedirect();
    $this->assertAuthenticated();
});

test('moneta:install refuses to run twice', function (): void {
    User::factory()->create();

    $this->artisan('moneta:install', [
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
        '--password' => 'super-secret',
        '--company' => 'Acme',
    ])->assertFailed();

    expect(User::query()->count())->toBe(1);
});

test('moneta:install validates input', function (): void {
    $this->artisan('moneta:install', [
        '--name' => 'Admin',
        '--email' => 'not-an-email',
        '--password' => 'short',
        '--company' => 'Acme',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

test('there are no registration routes', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
