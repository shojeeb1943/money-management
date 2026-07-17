<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Obligations\CreateObligation;
use App\Enums\ObligationKind;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
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

test('creating an obligation logs an audit entry that can be restored', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $this->actingAs($user)->post(route('obligations.store', ['current_company' => $company->slug]), [
        'kind' => ObligationKind::Lend->value,
        'label' => 'Lent to Rafi',
        'wallet_id' => $wallet->id,
        'amount' => '500',
    ])->assertRedirect();

    $obligation = Obligation::query()->where('label', 'Lent to Rafi')->firstOrFail();

    $log = AuditLog::query()
        ->where('auditable_type', Obligation::class)
        ->where('auditable_id', $obligation->id)
        ->where('action', 'created')
        ->firstOrFail();

    expect($obligation->transaction_id)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $log->id]))
        ->assertRedirect();

    expect(Obligation::query()->find($obligation->id))->toBeNull()
        ->and($obligation->transaction->fresh()->status)->toBe(TransactionStatus::Voided);

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $log->id]))
        ->assertRedirect()
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['type'] === 'error');
});

test('an obligation with payments cannot have its creation restored', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $this->actingAs($user)->post(route('obligations.store', ['current_company' => $company->slug]), [
        'kind' => ObligationKind::Lend->value,
        'label' => 'Lent to Karim',
        'wallet_id' => $wallet->id,
        'amount' => '1000',
    ])->assertRedirect();

    $obligation = Obligation::query()->where('label', 'Lent to Karim')->firstOrFail();
    $log = AuditLog::query()->where('auditable_id', $obligation->id)->where('action', 'created')->firstOrFail();

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '200',
            'date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $log->id]))
        ->assertRedirect()
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['type'] === 'error');

    expect(Obligation::query()->find($obligation->id))->not->toBeNull();
});

test('recording a payment logs an audit entry that can be restored, newest first', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $obligation = resolve(CreateObligation::class)->handle($company, ObligationKind::Lend, 'Lent to Nusrat', $wallet, 100_000, creator: $user);

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '300',
            'date' => now()->toDateString(),
        ])->assertRedirect();

    $firstLog = AuditLog::query()->where('auditable_id', $obligation->id)->where('action', 'paid')->firstOrFail();

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '200',
            'date' => now()->toDateString(),
        ])->assertRedirect();

    $secondLog = AuditLog::query()->where('auditable_id', $obligation->id)->where('action', 'paid')->where('id', '!=', $firstLog->id)->firstOrFail();

    expect($obligation->refresh()->remaining)->toBe(50_000);

    // restoring the older payment first must be rejected — it would desync `remaining`.
    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $firstLog->id]))
        ->assertRedirect()
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['type'] === 'error');

    expect($obligation->refresh()->remaining)->toBe(50_000);

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $secondLog->id]))
        ->assertRedirect();

    expect($obligation->refresh()->remaining)->toBe(70_000)
        ->and($obligation->status)->toBe('active');

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $firstLog->id]))
        ->assertRedirect();

    expect($obligation->refresh()->remaining)->toBe(100_000);
});

test('archiving an obligation logs an audit entry that can be restored', function (): void {
    $user = User::factory()->create();
    $company = obligationCompany($user);
    $wallet = Wallet::query()->firstOrFail();

    $obligation = resolve(CreateObligation::class)->handle($company, ObligationKind::Loan, 'Borrowed from Farhan', $wallet, 20_000, creator: $user);

    $this->actingAs($user)
        ->patch(route('obligations.archive', ['current_company' => $company->slug, 'obligation' => $obligation->id]))
        ->assertRedirect();

    expect($obligation->refresh()->isArchived())->toBeTrue();

    $log = AuditLog::query()->where('auditable_id', $obligation->id)->where('action', 'archived')->firstOrFail();

    $this->actingAs($user)
        ->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $log->id]))
        ->assertRedirect();

    expect($obligation->refresh()->isArchived())->toBeFalse();
});
