<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateCrossCompanyTransfer;
use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;

function twoCompanies(User $user): array
{
    $personal = resolve(CreateCompany::class)->handle($user, 'Personal');
    $bytesis = resolve(CreateCompany::class)->handle($user, 'Bytesis');

    return [$personal, $bytesis];
}

test('a cross-company transfer posts a capital withdrawal on the source and a capital investment on the destination', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $cash = Wallet::query()->where('name', 'Cash')->firstOrFail();

    [$out, $in] = resolve(CreateCrossCompanyTransfer::class)->handle(
        $personal, $bank, $bytesis, $cash, 50_000, now(), creator: $user,
    );

    expect($out->type)->toBe(TransactionType::CapitalWithdrawal)
        ->and($out->company_id)->toBe($personal->id)
        ->and($out->wallet_id)->toBe($bank->id)
        ->and($in->type)->toBe(TransactionType::CapitalInvestment)
        ->and($in->company_id)->toBe($bytesis->id)
        ->and($in->wallet_id)->toBe($cash->id)
        ->and($out->reference)->toBe($in->reference)
        ->and($bank->refresh()->cached_balance)->toBe($bank->opening_balance - 50_000)
        ->and($cash->refresh()->cached_balance)->toBe($cash->opening_balance + 50_000);
});

test('a cross-company transfer rejects the same company on both sides', function (): void {
    $user = User::factory()->create();
    [$personal] = twoCompanies($user);

    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $cash = Wallet::query()->where('name', 'Cash')->firstOrFail();

    expect(fn () => resolve(CreateCrossCompanyTransfer::class)->handle($personal, $bank, $personal, $cash, 10_000, now()))
        ->toThrow(InvalidArgumentException::class, 'same company');
});

test('a cross-company transfer rejects mismatched currencies', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $cash = Wallet::query()->where('name', 'Cash')->firstOrFail();
    $cash->update(['currency' => 'USD']);

    expect(fn () => resolve(CreateCrossCompanyTransfer::class)->handle($personal, $bank, $bytesis, $cash, 10_000, now()))
        ->toThrow(InvalidArgumentException::class, 'different currencies');
});

test('the cross-company transfer endpoint moves money between two companies', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $bank = Wallet::query()->where('name', 'Bank')->firstOrFail();
    $cash = Wallet::query()->where('name', 'Cash')->firstOrFail();

    $this->actingAs($user)->post(route('cross-company-transfers.store'), [
        'from_wallet_id' => $bank->id,
        'from_company_id' => $personal->id,
        'to_wallet_id' => $cash->id,
        'to_company_id' => $bytesis->id,
        'amount' => '500.00',
        'date' => now()->toDateString(),
        'description' => 'Owner draw',
    ])->assertRedirect();

    expect($bank->refresh()->cached_balance)->toBe($bank->opening_balance - 50_000)
        ->and($cash->refresh()->cached_balance)->toBe($cash->opening_balance + 50_000);
});
