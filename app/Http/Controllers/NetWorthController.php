<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Wallet;
use App\Services\Reports\IncomeStatementReport;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

final class NetWorthController extends Controller
{
    public function __invoke(IncomeStatementReport $incomeStatement): Response
    {
        $from = Date::now()->startOfMonth();
        $to = Date::now()->endOfMonth();

        $companies = Company::query()->orderBy('name')->get()->map(function (Company $company) use ($incomeStatement, $from, $to): array {
            $statement = $incomeStatement->generate($company, $from, $to);
            $wallets = Wallet::query()->active()->orderBy('name')->get();

            return [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'currency' => $company->currency,
                'totalCash' => (int) $wallets->where('currency', $company->currency)->sum('cached_balance'),
                'periodIncome' => $statement['totalIncome'],
                'periodExpense' => $statement['totalExpense'],
                'periodProfit' => $statement['netProfit'],
                'wallets' => $wallets->map(fn (Wallet $wallet): array => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'currency' => $wallet->currency,
                ])->values(),
            ];
        });

        return Inertia::render('net-worth', [
            'companies' => $companies->values(),
            'totalsByCurrency' => $companies->groupBy('currency')->map(fn ($group) => $group->sum('totalCash')),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
