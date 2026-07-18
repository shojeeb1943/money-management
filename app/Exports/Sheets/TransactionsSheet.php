<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class TransactionsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Company $company) {}

    public function collection(): Collection
    {
        return Transaction::query()
            ->where('company_id', $this->company->id)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'date' => $transaction->date->toDateString(),
                'type' => $transaction->type->label(),
                'wallet' => $transaction->wallet->name,
                'counter_wallet' => $transaction->counterWallet?->name,
                'category' => $transaction->category?->name,
                'amount' => $transaction->amount / 100,
                'currency' => $transaction->currency,
                'description' => $transaction->description,
                'reference' => $transaction->reference,
                'status' => $transaction->status->label(),
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Date', 'Type', 'Wallet', 'Counter Wallet', 'Category', 'Amount', 'Currency', 'Description', 'Reference', 'Status'];
    }

    public function title(): string
    {
        return 'Transactions';
    }
}
