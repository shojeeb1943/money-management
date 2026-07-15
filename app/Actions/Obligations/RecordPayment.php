<?php

declare(strict_types=1);

namespace App\Actions\Obligations;

use App\Actions\Transactions\CreateTransaction;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordPayment
{
    public function __construct(private CreateTransaction $createTransaction) {}

    public function handle(
        Company $company,
        Obligation $obligation,
        Wallet $wallet,
        int $amount,
        CarbonInterface $date,
        ?string $description = null,
        ?User $creator = null,
    ): ObligationPayment {
        throw_if($amount < 1, InvalidArgumentException::class, 'Amount must be positive.');
        throw_if($amount > $obligation->remaining, InvalidArgumentException::class, 'Payment exceeds remaining balance.');

        return DB::transaction(function () use ($company, $obligation, $wallet, $amount, $date, $description, $creator): ObligationPayment {
            // loan & safekeeping: payment goes OUT (repaying/returning)
            // lend: payment comes IN (they're repaying you)
            $txType = $obligation->kind === ObligationKind::Lend
                ? TransactionType::Income
                : TransactionType::Expense;

            $direction = $txType === TransactionType::Income ? 'in' : 'out';

            $payment = ObligationPayment::query()->create([
                'obligation_id' => $obligation->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'direction' => $direction,
                'date' => $date->toDateString(),
                'description' => $description,
                'created_by' => $creator?->id,
            ]);

            $obligation->update([
                'remaining' => $obligation->remaining - $amount,
                'status' => ($obligation->remaining - $amount) === 0 ? 'settled' : 'active',
            ]);

            $this->createTransaction->handle(
                $company,
                $txType,
                $wallet,
                $amount,
                $date,
                category: null,
                description: $description ?? sprintf('%s payment: %s', $obligation->kind->label(), $obligation->label),
                creator: $creator,
            );

            return $payment;
        });
    }
}
