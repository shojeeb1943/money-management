<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(
        Transaction $transaction,
        Wallet $wallet,
        int $amount,
        CarbonInterface $date,
        ?Category $category = null,
        ?string $description = null,
        ?string $reference = null,
    ): Transaction {
        throw_unless($transaction->isPosted(), InvalidArgumentException::class, 'A voided transaction cannot be edited.');

        throw_if($amount < 1, InvalidArgumentException::class, 'Amount must be positive.');

        return DB::transaction(function () use ($transaction, $wallet, $amount, $date, $category, $description, $reference): Transaction {
            $this->applyBalance->handle($transaction, direction: -1);

            $transaction->update([
                'wallet_id' => $wallet->id,
                'category_id' => $category?->id,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description,
                'reference' => $reference,
            ]);

            $this->applyBalance->handle($transaction->refresh());

            return $transaction;
        });
    }
}
