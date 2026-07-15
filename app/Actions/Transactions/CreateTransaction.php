<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(
        Company $company,
        TransactionType $type,
        Wallet $wallet,
        int $amount,
        CarbonInterface $date,
        ?Category $category = null,
        ?string $description = null,
        ?string $reference = null,
        ?User $creator = null,
    ): Transaction {
        $this->validate($company, $type, $wallet, $amount, $category);

        return DB::transaction(function () use ($company, $type, $wallet, $amount, $date, $category, $description, $reference, $creator) {
            $transaction = Transaction::query()->create([
                'company_id' => $company->id,
                'type' => $type,
                'wallet_id' => $wallet->id,
                'category_id' => $category?->id,
                'currency' => $wallet->currency,
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

    private function validate(Company $company, TransactionType $type, Wallet $wallet, int $amount, ?Category $category): void
    {
        throw_if($amount < 1, InvalidArgumentException::class, 'Amount must be positive.');

        throw_if($type === TransactionType::Transfer, InvalidArgumentException::class, 'Use CreateTransfer for transfers.');

        if ($type->requiresCategory()) {
            throw_if(! $category instanceof Category, InvalidArgumentException::class, 'This transaction type requires a category.');
            throw_if($category->kind !== $type->categoryKind(), InvalidArgumentException::class, 'Category kind does not match the transaction type.');
        } elseif ($category instanceof Category) {
            throw new InvalidArgumentException('This transaction type does not take a category.');
        }
    }
}
