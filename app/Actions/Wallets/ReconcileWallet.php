<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Actions\Categories\CreateCategory;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

final readonly class ReconcileWallet
{
    public const string ADJUSTMENT_CATEGORY = 'Balance Adjustment';

    public function __construct(
        private CreateTransaction $createTransaction,
        private CreateCategory $createCategory,
    ) {}

    public function handle(Wallet $wallet, int $actualBalance, Company $company, ?User $user = null): ?Transaction
    {
        $wallet->refresh();

        $difference = $actualBalance - $wallet->cached_balance;

        if ($difference === 0) {
            return null;
        }

        return DB::transaction(function () use ($wallet, $company, $difference, $user): Transaction {
            $kind = $difference > 0 ? CategoryKind::Income : CategoryKind::Expense;
            $category = $this->adjustmentCategory($kind);

            return $this->createTransaction->handle(
                $company,
                $difference > 0 ? TransactionType::Income : TransactionType::Expense,
                $wallet,
                abs($difference),
                now($company->timezone),
                $category,
                'Balance reconciliation for '.$wallet->name,
                creator: $user,
            );
        });
    }

    private function adjustmentCategory(CategoryKind $kind): Category
    {
        $existing = Category::query()
            ->where('kind', $kind)
            ->where('name', self::ADJUSTMENT_CATEGORY)
            ->first();

        return $existing ?? $this->createCategory->handle(
            self::ADJUSTMENT_CATEGORY,
            $kind,
            icon: 'scale',
            color: '#64748b',
        );
    }
}
