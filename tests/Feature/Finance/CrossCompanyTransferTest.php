<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateCrossCompanyTransfer;
use App\Enums\TransactionType;
use App\Models\User;

function twoCompanies(User $user): array
{
    $personal = resolve(CreateCompany::class)->handle($user, 'Personal');
    $bytesis = resolve(CreateCompany::class)->handle($user, 'Bytesis');

    return [$personal, $bytesis];
}

test('a cross-company transfer posts a capital withdrawal on the source and a capital investment on the destination', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $personalBank = $personal->wallets()->where('name', 'Bank')->firstOrFail();
    $bytesisBank = $bytesis->wallets()->where('name', 'Bank')->firstOrFail();

    [$out, $in] = resolve(CreateCrossCompanyTransfer::class)->handle(
        $personalBank, $bytesisBank, 50_000, now(), creator: $user,
    );

    expect($out->type)->toBe(TransactionType::CapitalWithdrawal)
        ->and($out->company_id)->toBe($personal->id)
        ->and($out->wallet_id)->toBe($personalBank->id)
        ->and($in->type)->toBe(TransactionType::CapitalInvestment)
        ->and($in->company_id)->toBe($bytesis->id)
        ->and($in->wallet_id)->toBe($bytesisBank->id)
        ->and($out->reference)->toBe($in->reference)
        ->and($personalBank->refresh()->cached_balance)->toBe($personalBank->opening_balance - 50_000)
        ->and($bytesisBank->refresh()->cached_balance)->toBe($bytesisBank->opening_balance + 50_000);
});

test('a cross-company transfer rejects two wallets from the same company', function (): void {
    $user = User::factory()->create();
    [$personal] = twoCompanies($user);

    $bank = $personal->wallets()->where('name', 'Bank')->firstOrFail();
    $cash = $personal->wallets()->where('name', 'Cash')->firstOrFail();

    expect(fn () => resolve(CreateCrossCompanyTransfer::class)->handle($bank, $cash, 10_000, now()))
        ->toThrow(InvalidArgumentException::class, 'same company');
});

test('a cross-company transfer rejects mismatched currencies', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $personalBank = $personal->wallets()->where('name', 'Bank')->firstOrFail();
    $bytesisBank = $bytesis->wallets()->where('name', 'Bank')->firstOrFail();
    $bytesisBank->update(['currency' => 'USD']);

    expect(fn () => resolve(CreateCrossCompanyTransfer::class)->handle($personalBank, $bytesisBank, 10_000, now()))
        ->toThrow(InvalidArgumentException::class, 'different currencies');
});

test('the cross-company transfer endpoint moves money between two companies', function (): void {
    $user = User::factory()->create();
    [$personal, $bytesis] = twoCompanies($user);

    $personalBank = $personal->wallets()->where('name', 'Bank')->firstOrFail();
    $bytesisBank = $bytesis->wallets()->where('name', 'Bank')->firstOrFail();

    $this->actingAs($user)->post(route('cross-company-transfers.store'), [
        'from_wallet_id' => $personalBank->id,
        'to_wallet_id' => $bytesisBank->id,
        'amount' => '500.00',
        'date' => now()->toDateString(),
        'description' => 'Owner draw',
    ])->assertRedirect();

    expect($personalBank->refresh()->cached_balance)->toBe($personalBank->opening_balance - 50_000)
        ->and($bytesisBank->refresh()->cached_balance)->toBe($bytesisBank->opening_balance + 50_000);
});
