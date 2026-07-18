<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\BudgetsSheet;
use App\Exports\Sheets\CategoriesSheet;
use App\Exports\Sheets\ObligationPaymentsSheet;
use App\Exports\Sheets\ObligationsSheet;
use App\Exports\Sheets\RecurringSheet;
use App\Exports\Sheets\TransactionsSheet;
use App\Exports\Sheets\WalletsSheet;
use App\Models\Budget;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class CompanyDataExport implements WithMultipleSheets
{
    public function __construct(private readonly Company $company) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $transactions = Transaction::query()->where('company_id', $this->company->id)
            ->get(['wallet_id', 'counter_wallet_id', 'category_id']);
        $recurring = RecurringTransaction::query()->where('company_id', $this->company->id)
            ->get(['wallet_id', 'counter_wallet_id', 'category_id']);
        $budgets = Budget::query()->where('company_id', $this->company->id)->get(['category_id']);
        $obligations = Obligation::query()->where('company_id', $this->company->id)->get(['wallet_id']);

        $walletIds = collect([
            $transactions->pluck('wallet_id'),
            $transactions->pluck('counter_wallet_id'),
            $recurring->pluck('wallet_id'),
            $recurring->pluck('counter_wallet_id'),
            $obligations->pluck('wallet_id'),
        ])->flatten()->filter()->unique()->values();

        $categoryIds = collect([
            $transactions->pluck('category_id'),
            $recurring->pluck('category_id'),
            $budgets->pluck('category_id'),
        ])->flatten()->filter()->unique()->values();

        return [
            new WalletsSheet($walletIds),
            new TransactionsSheet($this->company),
            new CategoriesSheet($categoryIds),
            new BudgetsSheet($this->company),
            new ObligationsSheet($this->company),
            new ObligationPaymentsSheet($this->company),
            new RecurringSheet($this->company),
        ];
    }
}
