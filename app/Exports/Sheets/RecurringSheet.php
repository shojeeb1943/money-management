<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Company;
use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class RecurringSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Company $company) {}

    public function collection(): Collection
    {
        return RecurringTransaction::query()
            ->where('company_id', $this->company->id)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderBy('name')
            ->get()
            ->map(fn (RecurringTransaction $recurring): array => [
                'name' => $recurring->name,
                'type' => $recurring->type->label(),
                'wallet' => $recurring->wallet->name,
                'counter_wallet' => $recurring->counterWallet?->name,
                'category' => $recurring->category?->name,
                'amount' => $recurring->amount / 100,
                'currency' => $recurring->currency,
                'frequency' => $recurring->frequency->label(),
                'interval' => $recurring->interval,
                'next_run' => $recurring->next_run_on->toDateString(),
                'last_run' => $recurring->last_run_on?->toDateString(),
                'active' => $recurring->is_active ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Name', 'Type', 'Wallet', 'Counter Wallet', 'Category', 'Amount', 'Currency', 'Frequency', 'Interval', 'Next Run', 'Last Run', 'Active'];
    }

    public function title(): string
    {
        return 'Recurring';
    }
}
