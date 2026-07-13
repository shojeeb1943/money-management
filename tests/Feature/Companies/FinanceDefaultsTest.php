<?php

use App\Actions\Companies\CreateCompany;
use App\Enums\CompanyPermission;
use App\Enums\CompanyRole;
use App\Models\User;

test('a new company gets default BD wallets and categories', function () {
    $user = User::factory()->create();

    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    expect($company->wallets()->pluck('name')->sort()->values()->all())->toBe(['Bank', 'Card', 'Cash', 'Mobile Wallet'])
        ->and($company->categories()->where('kind', 'income')->count())->toBeGreaterThanOrEqual(2)
        ->and($company->categories()->where('kind', 'expense')->count())->toBeGreaterThanOrEqual(3);
});

test('the installed admin company gets defaults too', function () {
    $this->artisan('moneta:install', [
        '--name' => 'Test User',
        '--email' => 'test@example.com',
        '--password' => 'super-secret',
        '--company' => 'My Company',
    ])->assertSuccessful();

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->currentCompany->wallets()->count())->toBe(4);
});

test('finance permissions follow the role matrix', function (CompanyRole $role, CompanyPermission $permission, bool $allowed) {
    expect($role->hasPermission($permission))->toBe($allowed);
})->with([
    'owner records' => [CompanyRole::Owner, CompanyPermission::RecordTransactions, true],
    'owner configures' => [CompanyRole::Owner, CompanyPermission::ManageFinanceSetup, true],
    'admin records' => [CompanyRole::Admin, CompanyPermission::RecordTransactions, true],
    'admin configures' => [CompanyRole::Admin, CompanyPermission::ManageFinanceSetup, true],
    'member records' => [CompanyRole::Member, CompanyPermission::RecordTransactions, false],
    'member configures' => [CompanyRole::Member, CompanyPermission::ManageFinanceSetup, false],
]);
