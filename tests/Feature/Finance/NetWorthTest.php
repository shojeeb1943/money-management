<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia;

test('the net worth page reports each company total plus a combined total per currency', function (): void {
    $user = User::factory()->create();
    resolve(CreateCompany::class)->handle($user, 'Personal');
    resolve(CreateCompany::class)->handle($user, 'Bytesis');

    $expectedTotal = Wallet::query()->sum('cached_balance');
    $expectedCompanyCount = Company::query()->count();

    $this->actingAs($user)
        ->get(route('net-worth'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('net-worth')
            ->count('companies', $expectedCompanyCount)
            ->where('totalsByCurrency.BDT', (int) $expectedTotal));
});
