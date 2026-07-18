<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Wallet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class WalletsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, int>  $walletIds
     */
    public function __construct(private readonly Collection $walletIds) {}

    public function collection(): Collection
    {
        return Wallet::query()
            ->whereIn('id', $this->walletIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet): array => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type->label(),
                'account_number' => $wallet->account_number,
                'currency' => $wallet->currency,
                'opening_balance' => $wallet->opening_balance / 100,
                'current_balance' => $wallet->cached_balance / 100,
                'archived' => $wallet->isArchived() ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Type', 'Account Number', 'Currency', 'Opening Balance', 'Current Balance', 'Archived'];
    }

    public function title(): string
    {
        return 'Wallets';
    }
}
