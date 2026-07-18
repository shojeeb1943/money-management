<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Budget;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class BudgetsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Company $company) {}

    public function collection(): Collection
    {
        return Budget::query()
            ->where('company_id', $this->company->id)
            ->with('category')
            ->orderBy('category_id')
            ->get()
            ->map(fn (Budget $budget): array => [
                'category' => $budget->category->name,
                'period' => $budget->period,
                'amount' => $budget->amount / 100,
                'alert_threshold' => $budget->alert_threshold,
                'active' => $budget->is_active ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Category', 'Period', 'Amount', 'Alert Threshold (%)', 'Active'];
    }

    public function title(): string
    {
        return 'Budgets';
    }
}
