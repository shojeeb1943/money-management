<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Obligations\CreateObligation;
use App\Enums\ObligationKind;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\User;
use App\Models\Wallet;

function obligationCompany(User $user): Company
{
    return resolve(CreateCompany::class)->handle($user, 'Acme Studio');
}

test('recording a payment on a lend obligation decrements remaining and settles it', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $obligation = resolve(CreateObligation::class)->handle(
        $company,
        ObligationKind::Lend,
        'Loaned to Rafi',
        $wallet,
        100_000,
        creator: $user,
    );

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '400',
            'date' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($obligation->refresh()->remaining)->toBe(60_000)
        ->and($obligation->status)->toBe('active');

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '600',
            'date' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($obligation->refresh()->remaining)->toBe(0)
        ->and($obligation->status)->toBe('settled');
});

test('an obligation can be archived and restored', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $obligation = resolve(CreateObligation::class)->handle(
        $company,
        ObligationKind::Loan,
        'Borrowed from Nadia',
        $wallet,
        50_000,
        creator: $user,
    );

    $this->actingAs($user)
        ->patch(route('obligations.archive', ['current_company' => $company->slug, 'obligation' => $obligation->id]))
        ->assertRedirect();

    expect($obligation->refresh()->isArchived())->toBeTrue();

    $this->actingAs($user)
        ->patch(route('obligations.archive', ['current_company' => $company->slug, 'obligation' => $obligation->id]))
        ->assertRedirect();

    expect($obligation->refresh()->isArchived())->toBeFalse();
});
