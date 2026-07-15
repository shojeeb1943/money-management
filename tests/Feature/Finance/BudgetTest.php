<?php

declare(strict_types=1);

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Actions\Categories\CreateCategory;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia;

function budgetSetup(): array
{
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $marketing = Category::query()->where('name', 'Marketing')->firstOrFail();

    return [$user, $company, $bank, $marketing];
}

test('crossing the alert threshold produces a warning and exceeding produces an error', function (): void {
    [$user, $company, $bank, $marketing] = budgetSetup();

    Budget::query()->create([
        'company_id' => $company->id,
        'category_id' => $marketing->id,
        'amount' => 100_000,
        'alert_threshold' => 80,
    ]);

    $evaluator = resolve(EvaluateBudgetAlert::class);

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 50_000, now(), $marketing);
    expect($evaluator->handle($company, $marketing, now()))->toBeNull();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 35_000, now(), $marketing);
    $warning = $evaluator->handle($company, $marketing, now());
    expect($warning)->not->toBeNull()
        ->and($warning['level'])->toBe('warning')
        ->and($warning['message'])->toContain('85%');

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 20_000, now(), $marketing);
    $exceeded = $evaluator->handle($company, $marketing, now());
    expect($exceeded['level'])->toBe('error')
        ->and($exceeded['message'])->toContain('exceeded');
});

test('child category spend counts toward the parent budget', function (): void {
    [$user, $company, $bank, $marketing] = budgetSetup();

    $childCategory = resolve(CreateCategory::class)->handle('Facebook Ads', CategoryKind::Expense, $marketing);

    Budget::query()->create([
        'company_id' => $company->id,
        'category_id' => $marketing->id,
        'amount' => 100_000,
        'alert_threshold' => 80,
    ]);

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 90_000, now(), $childCategory);

    $alert = resolve(EvaluateBudgetAlert::class)->handle($company, $childCategory, now());

    expect($alert)->not->toBeNull()
        ->and($alert['level'])->toBe('warning')
        ->and($alert['message'])->toContain('Marketing');
});

test('a budget alert is flashed when recording an expense through the endpoint', function (): void {
    [$user, $company, $bank, $marketing] = budgetSetup();

    Budget::query()->create([
        'company_id' => $company->id,
        'category_id' => $marketing->id,
        'amount' => 100_000,
        'alert_threshold' => 80,
    ]);

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => TransactionType::Expense->value,
        'wallet_id' => $bank->id,
        'category_id' => $marketing->id,
        'amount' => '900',
        'date' => now()->toDateString(),
    ])->assertRedirect();

    expect($company->transactions()->count())->toBe(1);
});

test('budgets can be created and removed through the endpoints', function (): void {
    [$user, $company, , $marketing] = budgetSetup();

    $this->actingAs($user)->post(route('budgets.store', ['current_company' => $company->slug]), [
        'category_id' => $marketing->id,
        'period' => 'monthly',
        'amount' => '50000',
        'alert_threshold' => 75,
    ])->assertRedirect();

    $budget = Budget::query()->forCompany($company)->firstOrFail();

    expect($budget->amount)->toBe(5_000_000)
        ->and($budget->alert_threshold)->toBe(75);

    $this->actingAs($user)->post(route('budgets.store', ['current_company' => $company->slug]), [
        'category_id' => $marketing->id,
        'period' => 'monthly',
        'amount' => '60000',
        'alert_threshold' => 90,
    ])->assertRedirect();

    expect(Budget::query()->forCompany($company)->count())->toBe(1)
        ->and($budget->refresh()->amount)->toBe(6_000_000);

    $this->actingAs($user)
        ->delete(route('budgets.destroy', ['current_company' => $company->slug, 'budget' => $budget->id]))
        ->assertRedirect();

    expect(Budget::query()->forCompany($company)->count())->toBe(0);
});

test('the dashboard shows budget progress', function (): void {
    [$user, $company, $bank, $marketing] = budgetSetup();

    Budget::query()->create([
        'company_id' => $company->id,
        'category_id' => $marketing->id,
        'amount' => 100_000,
        'alert_threshold' => 80,
        'period' => 'monthly',
        'is_active' => true,
    ]);

    resolve(CreateTransaction::class)->handle(
        $company, TransactionType::Expense, $bank, 45_000, now(), $marketing, creator: $user,
    );

    $user->switchCompany($company);

    $this->actingAs($user)
        ->get(route('dashboard', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('dashboard')
            ->has('budgets', 1)
            ->where('budgets.0.categoryName', 'Marketing')
            ->where('budgets.0.spent', 45_000)
            ->where('budgets.0.amount', 100_000));
});
