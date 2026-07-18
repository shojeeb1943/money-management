<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Company;
use App\Models\ObligationPayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ObligationPaymentsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Company $company) {}

    public function collection(): Collection
    {
        return ObligationPayment::query()
            ->where('company_id', $this->company->id)
            ->with(['obligation', 'wallet'])
            ->orderBy('date')
            ->get()
            ->map(fn (ObligationPayment $payment): array => [
                'date' => $payment->date->toDateString(),
                'obligation' => $payment->obligation->label,
                'wallet' => $payment->wallet->name,
                'amount' => $payment->amount / 100,
                'direction' => $payment->direction,
                'description' => $payment->description,
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Date', 'Obligation', 'Wallet', 'Amount', 'Direction', 'Description'];
    }

    public function title(): string
    {
        return 'Obligation Payments';
    }
}
