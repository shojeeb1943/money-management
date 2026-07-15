<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UnvoidTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(Transaction $transaction): Transaction
    {
        throw_unless($transaction->status === TransactionStatus::Voided, InvalidArgumentException::class, 'Transaction is not voided.');

        return DB::transaction(function () use ($transaction): Transaction {
            $transaction->update(['status' => TransactionStatus::Posted]);

            $this->applyBalance->handle($transaction);

            return $transaction;
        });
    }
}
