<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;

/**
 * @return array{0: User, 1: Company}
 */
function restoreSetup(): array
{
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    return [$user, $company];
}

test('restoring a voided transaction un-voids it and restores the wallet balance', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $sales = $company->categories()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $sales->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);
    $transaction = $company->transactions()->firstOrFail();
    $balanceBeforeVoid = $bank->refresh()->cached_balance;

    $this->actingAs($user)->delete(route('transactions.destroy', ['current_company' => $company->slug, 'transaction' => $transaction->id]));
    $voidedLog = AuditLog::query()->forCompany($company)->where('action', 'voided')->firstOrFail();

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $voidedLog->id]));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Posted)
        ->and($bank->refresh()->cached_balance)->toBe($balanceBeforeVoid)
        ->and($voidedLog->refresh()->restored_at)->not->toBeNull()
        ->and(AuditLog::query()->forCompany($company)->where('action', 'restored')->count())->toBe(1);
});

test('restoring a created transaction voids it', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $sales = $company->categories()->where('name', 'Sales')->firstOrFail();
    $balanceBefore = $bank->cached_balance;

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $sales->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);
    $transaction = $company->transactions()->firstOrFail();
    $createdLog = AuditLog::query()->forCompany($company)->where('action', 'created')->firstOrFail();

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $createdLog->id]));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Voided)
        ->and($bank->refresh()->cached_balance)->toBe($balanceBefore);
});

test('restoring an updated transaction reverts amount and wallet to their prior values', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();
    $sales = $company->categories()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $sales->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);
    $transaction = $company->transactions()->firstOrFail();
    $bankBalanceBeforeUpdate = $bank->refresh()->cached_balance;
    $cashBalanceBeforeUpdate = $cash->refresh()->cached_balance;

    $this->actingAs($user)->put(route('transactions.update', ['current_company' => $company->slug, 'transaction' => $transaction->id]), [
        'type' => 'income',
        'wallet_id' => $cash->id,
        'category_id' => $sales->id,
        'amount' => '6000',
        'date' => now()->toDateString(),
    ]);
    $updatedLog = AuditLog::query()->forCompany($company)->where('action', 'updated')->firstOrFail();

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $updatedLog->id]));

    expect($transaction->refresh()->wallet_id)->toBe($bank->id)
        ->and($transaction->amount)->toBe(500_000)
        ->and($bank->refresh()->cached_balance)->toBe($bankBalanceBeforeUpdate)
        ->and($cash->refresh()->cached_balance)->toBe($cashBalanceBeforeUpdate);
});

test('restoring a reconciliation voids the adjustment transaction it created', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $balanceBefore = $bank->cached_balance;

    $this->actingAs($user)->post(route('wallets.reconcile', ['current_company' => $company->slug, 'wallet' => $bank->id]), [
        'actual_balance' => sprintf('%.2f', ($balanceBefore + 10_000) / 100),
    ]);
    $reconciledLog = AuditLog::query()->forCompany($company)->where('action', 'reconciled')->firstOrFail();
    $adjustment = $company->transactions()->where('description', 'like', 'Balance reconciliation%')->firstOrFail();

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $reconciledLog->id]));

    expect($adjustment->refresh()->status)->toBe(TransactionStatus::Voided)
        ->and($bank->refresh()->cached_balance)->toBe($balanceBefore);
});

test('an already-restored audit entry cannot be restored again', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $sales = $company->categories()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $sales->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);
    $transaction = $company->transactions()->firstOrFail();
    $this->actingAs($user)->delete(route('transactions.destroy', ['current_company' => $company->slug, 'transaction' => $transaction->id]));
    $voidedLog = AuditLog::query()->forCompany($company)->where('action', 'voided')->firstOrFail();

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $voidedLog->id]));
    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $voidedLog->id]));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Posted)
        ->and(AuditLog::query()->forCompany($company)->where('action', 'restored')->count())->toBe(1);
});

test('a legacy updated entry without a before-snapshot cannot be restored', function (): void {
    [$user, $company] = restoreSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $sales = $company->categories()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $sales->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);
    $transaction = $company->transactions()->firstOrFail();

    $legacyLog = AuditLog::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'action' => 'updated',
        'auditable_type' => $transaction->getMorphClass(),
        'auditable_id' => $transaction->id,
        'changes' => ['amount' => ['from' => 500_000, 'to' => 600_000]],
        'created_at' => now(),
    ]);

    $this->actingAs($user)->post(route('audit.restore', ['current_company' => $company->slug, 'audit_log' => $legacyLog->id]));

    expect($legacyLog->refresh()->restored_at)->toBeNull()
        ->and($transaction->refresh()->amount)->toBe(500_000);
});
