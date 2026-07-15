<?php

declare(strict_types=1);

namespace App\Actions\Obligations;

use App\Actions\Transactions\CreateTransaction;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

final readonly class CreateObligation
{
    public function __construct(private CreateTransaction $createTransaction) {}

    public function handle(
        Company $company,
        ObligationKind $kind,
        string $label,
        Wallet $wallet,
        int $amount,
        ?string $description = null,
        ?User $creator = null,
    ): Obligation {
        return DB::transaction(function () use ($company, $kind, $label, $wallet, $amount, $description, $creator): Obligation {
            $obligation = Obligation::query()->create([
                'company_id' => $company->id,
                'kind' => $kind,
                'label' => $label,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'remaining' => $amount,
                'currency' => $wallet->currency,
                'description' => $description,
                'status' => 'active',
            ]);

            // loan & safekeeping: money comes IN (you receive it)
            // lend: money goes OUT (you give it)
            $txType = $kind === ObligationKind::Lend
                ? TransactionType::Expense
                : TransactionType::Income;

            $category = $this->obligationCategory($txType);

            $this->createTransaction->handle(
                $company,
                $txType,
                $wallet,
                $amount,
                now($company->timezone),
                category: $category,
                description: sprintf('%s: %s', $kind->label(), $label),
                creator: $creator,
            );

            return $obligation;
        });
    }

    private function obligationCategory(TransactionType $type): Category
    {
        return Category::query()->firstOrCreate(
            ['kind' => $type->categoryKind(), 'name' => 'Obligation', 'parent_id' => null],
            ['color' => '#6b7280'],
        );
    }
}
