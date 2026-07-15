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
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class CreateCrossCompanyTransfer
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    /**
     * @return array{0: Transaction, 1: Transaction}
     */
    public function handle(
        Company $fromCompany,
        Wallet $from,
        Company $toCompany,
        Wallet $to,
        int $amount,
        CarbonInterface $date,
        ?string $description = null,
        ?User $creator = null,
    ): array {
        throw_if($amount < 1, InvalidArgumentException::class, 'Amount must be positive.');

        throw_if($from->id === $to->id, InvalidArgumentException::class, 'Cannot transfer to the same wallet.');

        throw_if($fromCompany->id === $toCompany->id, InvalidArgumentException::class, 'Use a regular transfer for wallets in the same company.');

        throw_if($from->currency !== $to->currency, InvalidArgumentException::class, 'Transfers between different currencies are not supported yet.');

        return DB::transaction(function () use ($fromCompany, $from, $toCompany, $to, $amount, $date, $description, $creator) {
            $reference = 'XFER-'.Str::ulid();

            $out = Transaction::query()->create([
                'company_id' => $fromCompany->id,
                'type' => TransactionType::CapitalWithdrawal,
                'wallet_id' => $from->id,
                'currency' => $from->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description ?? "Transfer to {$toCompany->name} — {$to->name}",
                'reference' => $reference,
                'status' => TransactionStatus::Posted,
                'created_by' => $creator?->id,
            ]);

            $in = Transaction::query()->create([
                'company_id' => $toCompany->id,
                'type' => TransactionType::CapitalInvestment,
                'wallet_id' => $to->id,
                'currency' => $to->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description ?? "Transfer from {$fromCompany->name} — {$from->name}",
                'reference' => $reference,
                'status' => TransactionStatus::Posted,
                'created_by' => $creator?->id,
            ]);

            $this->applyBalance->handle($out);
            $this->applyBalance->handle($in);

            return [$out, $in];
        });
    }
}
