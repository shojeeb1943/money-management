<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;

beforeEach(function (): void {
    $this->flag = storage_path('installed');
    @unlink($this->flag);
});

afterEach(function (): void {
    touch($this->flag);
});

test('creating the admin account finishes the installation', function (): void {
    $this->post(route('install.admin.store'), [
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
        'company' => 'Acme Studio',
    ])->assertRedirect(route('login'));

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $company = Company::query()->where('name', 'Acme Studio')->firstOrFail();

    expect($user->currentCompany->is($company))->toBeTrue()
        ->and(Wallet::query()->count())->toBeGreaterThan(0)
        ->and(file_exists($this->flag))->toBeTrue();

    $this->post(route('login.store'), [
        'email' => 'admin@example.com',
        'password' => 'super-secret',
    ])->assertRedirect();
    $this->assertAuthenticated();
});

test('the installer locks after the admin account is created', function (): void {
    $this->post(route('install.admin.store'), [
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
        'company' => 'Acme Studio',
    ])->assertRedirect(route('login'));

    $this->post(route('install.admin.store'), [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
        'company' => 'Evil Corp',
    ])->assertRedirect('/');

    expect(User::query()->count())->toBe(1);
});

test('the installer self-heals when users already exist', function (): void {
    User::factory()->create();

    $this->get(route('install.admin'))->assertRedirect('/');

    expect(file_exists($this->flag))->toBeTrue();

    $this->get('/login')->assertOk();
});

test('the database step is locked when users already exist without a flag', function (): void {
    User::factory()->create();

    $this->get(route('install.database'))->assertRedirect('/');
    $this->post(route('install.database.store'), ['connection' => 'sqlite'])->assertRedirect('/');

    expect(file_exists($this->flag))->toBeTrue();
});

test('the admin account requires a confirmed password', function (): void {
    $this->post(route('install.admin.store'), [
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'different',
        'company' => 'Acme Studio',
    ])->assertSessionHasErrors('password');

    expect(User::query()->count())->toBe(0);
});
