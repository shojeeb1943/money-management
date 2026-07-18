<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Enums\ObligationKind;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Obligation;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia;

function auditSetup(): array
{
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    return [$user, $company];
}

test('an audit trail is written for the transaction lifecycle', function (): void {
    [$user, $company] = auditSetup();
    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $commission = Category::query()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);

    $transaction = $company->transactions()->firstOrFail();

    $this->actingAs($user)->put(route('transactions.update', ['current_company' => $company->slug, 'transaction' => $transaction->id]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '6000',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)->delete(route('transactions.destroy', ['current_company' => $company->slug, 'transaction' => $transaction->id]));

    $actions = AuditLog::query()->forCompany($company)->pluck('action');

    expect($actions)->toContain('created', 'updated', 'voided');

    $updated = AuditLog::query()->forCompany($company)->where('action', 'updated')->firstOrFail();

    expect($updated->user_id)->toBe($user->id)
        ->and($updated->changes['amount'])->toBe(['from' => 500_000, 'to' => 600_000]);
});

test('the audit log page renders human-readable summaries instead of raw JSON', function (): void {
    [$user, $company] = auditSetup();
    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $commission = Category::query()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);

    $transaction = $company->transactions()->firstOrFail();

    $this->actingAs($user)->put(route('transactions.update', ['current_company' => $company->slug, 'transaction' => $transaction->id]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '6000',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)->delete(route('transactions.destroy', ['current_company' => $company->slug, 'transaction' => $transaction->id]));

    $this->actingAs($user)->post(route('obligations.store', ['current_company' => $company->slug]), [
        'kind' => ObligationKind::Lend->value,
        'label' => 'Lent to Rafi',
        'wallet_id' => $bank->id,
        'amount' => '3000',
    ]);

    $obligation = Obligation::query()->where('label', 'Lent to Rafi')->firstOrFail();

    $this->actingAs($user)
        ->post(route('obligations.pay', ['current_company' => $company->slug, 'obligation' => $obligation->id]), [
            'amount' => '3000',
            'date' => now()->toDateString(),
        ]);

    $this->actingAs($user)
        ->patch(route('obligations.archive', ['current_company' => $company->slug, 'obligation' => $obligation->id]));

    // Logs are ordered newest first: archived, paid, obligation created, voided, updated, created.
    $this->actingAs($user)
        ->get(route('audit.index', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('audit/index')
            ->has('logs', 6)
            ->where('logs.0.summary', 'Archived')
            ->where('logs.1.summary', 'Payment of ৳3,000 recorded (remaining ৳3,000 → ৳0)')
            ->where('logs.2.summary', 'Lend of ৳3,000 via Bank')
            ->where('logs.3.summary', 'Voided Income of ৳6,000')
            ->where('logs.4.summary', 'Amount changed from ৳5,000 to ৳6,000')
            ->where('logs.5.summary', 'Income of ৳5,000 in Bank'));
});
