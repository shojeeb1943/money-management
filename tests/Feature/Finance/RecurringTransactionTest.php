<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Recurring\ProcessRecurringTransactions;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Models\Wallet;

function recurringSetup(): array
{
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $rentCategory = Category::query()->where('name', 'Office Rent')->firstOrFail();

    return [$user, $company, $bank, $rentCategory];
}

test('a due recurring transaction creates a transaction and advances the schedule', function (): void {
    [$user, $company, $bank, $rent] = recurringSetup();

    $recurring = RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Office rent',
        'type' => TransactionType::Expense,
        'wallet_id' => $bank->id,
        'category_id' => $rent->id,
        'amount' => 5_000_000,
        'frequency' => RecurrenceFrequency::Monthly,
        'interval' => 1,
        'day_of_month' => 1,
        'starts_on' => today()->startOfMonth()->toDateString(),
        'next_run_on' => today()->startOfMonth()->toDateString(),
        'is_active' => true,
    ]);

    $processed = resolve(ProcessRecurringTransactions::class)->handle();

    expect($processed)->toBe(1)
        ->and($company->transactions()->count())->toBe(1)
        ->and($company->transactions()->first()->amount)->toBe(5_000_000)
        ->and($recurring->refresh()->next_run_on->toDateString())
        ->toBe(today()->startOfMonth()->addMonthsNoOverflow(1)->toDateString());

    $this->artisan('moneta:verify-balances')->assertSuccessful();
});

test('running the processor twice on the same day is idempotent', function (): void {
    [$user, $company, $bank, $rent] = recurringSetup();

    RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Office rent',
        'type' => TransactionType::Expense,
        'wallet_id' => $bank->id,
        'category_id' => $rent->id,
        'amount' => 5_000_000,
        'frequency' => RecurrenceFrequency::Monthly,
        'interval' => 1,
        'starts_on' => today()->toDateString(),
        'next_run_on' => today()->toDateString(),
        'is_active' => true,
    ]);

    resolve(ProcessRecurringTransactions::class)->handle();
    resolve(ProcessRecurringTransactions::class)->handle();

    expect($company->transactions()->count())->toBe(1);
});

test('the processor catches up missed periods', function (): void {
    [$user, $company, $bank, $rent] = recurringSetup();

    RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Weekly backup fee',
        'type' => TransactionType::Expense,
        'wallet_id' => $bank->id,
        'category_id' => $rent->id,
        'amount' => 10_000,
        'frequency' => RecurrenceFrequency::Weekly,
        'interval' => 1,
        'starts_on' => today()->subWeeks(3)->toDateString(),
        'next_run_on' => today()->subWeeks(3)->toDateString(),
        'is_active' => true,
    ]);

    resolve(ProcessRecurringTransactions::class)->handle();

    expect($company->transactions()->count())->toBe(4);
});

test('an inactive schedule and one past its end date are skipped', function (): void {
    [$user, $company, $bank, $rent] = recurringSetup();

    RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Paused',
        'type' => TransactionType::Expense,
        'wallet_id' => $bank->id,
        'category_id' => $rent->id,
        'amount' => 10_000,
        'frequency' => RecurrenceFrequency::Monthly,
        'interval' => 1,
        'starts_on' => today()->toDateString(),
        'next_run_on' => today()->toDateString(),
        'is_active' => false,
    ]);

    $ended = RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Ended',
        'type' => TransactionType::Expense,
        'wallet_id' => $bank->id,
        'category_id' => $rent->id,
        'amount' => 10_000,
        'frequency' => RecurrenceFrequency::Monthly,
        'interval' => 1,
        'starts_on' => today()->subMonths(2)->toDateString(),
        'ends_on' => today()->subMonth()->toDateString(),
        'next_run_on' => today()->toDateString(),
        'is_active' => true,
    ]);

    resolve(ProcessRecurringTransactions::class)->handle();

    expect($company->transactions()->count())->toBe(0)
        ->and($ended->refresh()->is_active)->toBeFalse();
});

test('a recurring transfer moves money between wallets', function (): void {
    [$user, $company, $bank] = recurringSetup();
    $cash = Wallet::query()->where('name', 'Cash')->firstOrFail();

    RecurringTransaction::query()->create([
        'company_id' => $company->id,
        'name' => 'Weekly cash top-up',
        'type' => TransactionType::Transfer,
        'wallet_id' => $bank->id,
        'counter_wallet_id' => $cash->id,
        'amount' => 100_000,
        'frequency' => RecurrenceFrequency::Weekly,
        'interval' => 1,
        'starts_on' => today()->toDateString(),
        'next_run_on' => today()->toDateString(),
        'is_active' => true,
    ]);

    resolve(ProcessRecurringTransactions::class)->handle();

    expect($bank->refresh()->cached_balance)->toBe(-100_000)
        ->and($cash->refresh()->cached_balance)->toBe(100_000);

    $this->artisan('moneta:verify-balances')->assertSuccessful();
});
