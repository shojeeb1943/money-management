<?php

declare(strict_types=1);

use App\Actions\Categories\CreateCategory;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;

function categoryCompany(User $user): Company
{
    return resolve(CreateCompany::class)->handle($user, 'Acme Studio');
}

test('a category can be created with a sub-category', function (): void {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Software',
        'kind' => CategoryKind::Expense->value,
    ])->assertRedirect();

    $parent = Category::query()->where('name', 'Software')->firstOrFail();

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Licenses',
        'kind' => CategoryKind::Expense->value,
        'parent_id' => $parent->id,
    ])->assertRedirect();

    $child = Category::query()->where('name', 'Licenses')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->kind->value)->toBe('expense');
});

test('categories cannot nest more than two levels', function (): void {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $parent = resolve(CreateCategory::class)->handle('Level 1', CategoryKind::Expense);
    $child = resolve(CreateCategory::class)->handle('Level 2', CategoryKind::Expense, $parent);

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Level 3',
        'kind' => CategoryKind::Expense->value,
        'parent_id' => $child->id,
    ])->assertSessionHasErrors('parent_id');
});

test('a category with ledger activity cannot be deleted but can be archived', function (): void {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $category = resolve(CreateCategory::class)->handle('Hosting Bills', CategoryKind::Expense);
    $wallet = Wallet::query()->firstOrFail();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $wallet, 30_000, now(), $category, 'Server payment');

    $this->actingAs($user)
        ->delete(route('categories.destroy', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect(Category::query()->find($category->id))->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('categories.archive', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect($category->refresh()->isArchived())->toBeTrue();
});

test('an unused category can be deleted', function (): void {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $category = resolve(CreateCategory::class)->handle('Unused', CategoryKind::Income);

    $this->actingAs($user)
        ->delete(route('categories.destroy', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect(Category::query()->find($category->id))->toBeNull();
});
