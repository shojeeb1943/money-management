<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Company;
use App\Models\Obligation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ObligationsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Company $company) {}

    public function collection(): Collection
    {
        return Obligation::query()
            ->where('company_id', $this->company->id)
            ->with('wallet')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Obligation $obligation): array => [
                'id' => $obligation->id,
                'kind' => $obligation->kind->label(),
                'label' => $obligation->label,
                'wallet' => $obligation->wallet->name,
                'amount' => $obligation->amount / 100,
                'remaining' => $obligation->remaining / 100,
                'currency' => $obligation->currency,
                'description' => $obligation->description,
                'status' => $obligation->status,
                'archived' => $obligation->isArchived() ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['ID', 'Kind', 'Label', 'Wallet', 'Amount', 'Remaining', 'Currency', 'Description', 'Status', 'Archived'];
    }

    public function title(): string
    {
        return 'Obligations';
    }
}
