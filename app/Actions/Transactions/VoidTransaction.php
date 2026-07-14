<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class VoidTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(Transaction $transaction): Transaction
    {
        throw_unless($transaction->isPosted(), InvalidArgumentException::class, 'Transaction is already voided.');

        return DB::transaction(function () use ($transaction): Transaction {
            $this->applyBalance->handle($transaction, direction: -1);

            $transaction->update(['status' => TransactionStatus::Voided]);

            return $transaction;
        });
    }
}
