<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateTransfer
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(
        Company $company,
        Wallet $from,
        Wallet $to,
        int $amount,
        CarbonInterface $date,
        ?string $description = null,
        ?string $reference = null,
        ?User $creator = null,
    ): Transaction {
        throw_if($amount < 1, InvalidArgumentException::class, 'Amount must be positive.');

        throw_if($from->id === $to->id, InvalidArgumentException::class, 'Cannot transfer to the same wallet.');

        throw_if($from->company_id !== $company->id || $to->company_id !== $company->id, InvalidArgumentException::class, 'Both wallets must belong to the company.');

        throw_if($from->currency !== $to->currency, InvalidArgumentException::class, 'Transfers between different currencies are not supported yet.');

        return DB::transaction(function () use ($company, $from, $to, $amount, $date, $description, $reference, $creator) {
            $transaction = Transaction::query()->create([
                'company_id' => $company->id,
                'type' => TransactionType::Transfer,
                'wallet_id' => $from->id,
                'counter_wallet_id' => $to->id,
                'currency' => $from->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description,
                'reference' => $reference,
                'status' => TransactionStatus::Posted,
                'created_by' => $creator?->id,
            ]);

            $this->applyBalance->handle($transaction);

            return $transaction;
        });
    }
}
