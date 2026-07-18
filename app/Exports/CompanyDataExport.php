<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CompanyDataExport
{
    public function __construct(private readonly Company $company) {}

    public function toResponse(): StreamedResponse
    {
        return response()->streamDownload(
            fn () => $this->write(),
            "finance-export-{$this->company->slug}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function write(): void
    {
        $transactions = Transaction::query()->where('company_id', $this->company->id)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderBy('date')->orderBy('id')->get();
        $recurring = RecurringTransaction::query()->where('company_id', $this->company->id)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderBy('name')->get();
        $budgets = Budget::query()->where('company_id', $this->company->id)
            ->with('category')->orderBy('category_id')->get();
        $obligations = Obligation::query()->where('company_id', $this->company->id)
            ->with('wallet')->orderBy('created_at')->get();
        $payments = ObligationPayment::query()->where('company_id', $this->company->id)
            ->with(['obligation', 'wallet'])->orderBy('date')->get();

        $walletIds = collect([
            $transactions->pluck('wallet_id'),
            $transactions->pluck('counter_wallet_id'),
            $recurring->pluck('wallet_id'),
            $recurring->pluck('counter_wallet_id'),
            $obligations->pluck('wallet_id'),
        ])->flatten()->filter()->unique()->values();
        $wallets = Wallet::query()->whereIn('id', $walletIds)->orderBy('name')->get();

        $categoryIds = collect([
            $transactions->pluck('category_id'),
            $recurring->pluck('category_id'),
            $budgets->pluck('category_id'),
        ])->flatten()->filter()->unique()->values();
        $categories = Category::query()->whereIn('id', $categoryIds)->with('parent')->orderBy('name')->get();

        $writer = new Writer();
        $writer->openToFile('php://output');

        $this->sheet($writer, true, 'Wallets',
            ['ID', 'Name', 'Type', 'Account Number', 'Currency', 'Opening Balance', 'Current Balance', 'Archived'],
            $wallets->map(fn (Wallet $wallet): array => [
                $wallet->id, $wallet->name, $wallet->type->label(), $wallet->account_number, $wallet->currency,
                $wallet->opening_balance / 100, $wallet->cached_balance / 100, $wallet->isArchived() ? 'Yes' : 'No',
            ]));

        $this->sheet($writer, false, 'Transactions',
            ['Date', 'Type', 'Wallet', 'Counter Wallet', 'Category', 'Amount', 'Currency', 'Description', 'Reference', 'Status'],
            $transactions->map(fn (Transaction $transaction): array => [
                $transaction->date->toDateString(), $transaction->type->label(), $transaction->wallet->name,
                $transaction->counterWallet?->name, $transaction->category?->name, $transaction->amount / 100,
                $transaction->currency, $transaction->description, $transaction->reference, $transaction->status->label(),
            ]));

        $this->sheet($writer, false, 'Categories',
            ['ID', 'Name', 'Kind', 'Parent', 'Archived'],
            $categories->map(fn (Category $category): array => [
                $category->id, $category->name, $category->kind->value, $category->parent?->name,
                $category->isArchived() ? 'Yes' : 'No',
            ]));

        $this->sheet($writer, false, 'Budgets',
            ['Category', 'Period', 'Amount', 'Alert Threshold (%)', 'Active'],
            $budgets->map(fn (Budget $budget): array => [
                $budget->category->name, $budget->period, $budget->amount / 100, $budget->alert_threshold,
                $budget->is_active ? 'Yes' : 'No',
            ]));

        $this->sheet($writer, false, 'Obligations',
            ['ID', 'Kind', 'Label', 'Wallet', 'Amount', 'Remaining', 'Currency', 'Description', 'Status', 'Archived'],
            $obligations->map(fn (Obligation $obligation): array => [
                $obligation->id, $obligation->kind->label(), $obligation->label, $obligation->wallet->name,
                $obligation->amount / 100, $obligation->remaining / 100, $obligation->currency,
                $obligation->description, $obligation->status, $obligation->isArchived() ? 'Yes' : 'No',
            ]));

        $this->sheet($writer, false, 'Obligation Payments',
            ['Date', 'Obligation', 'Wallet', 'Amount', 'Direction', 'Description'],
            $payments->map(fn (ObligationPayment $payment): array => [
                $payment->date->toDateString(), $payment->obligation->label, $payment->wallet->name,
                $payment->amount / 100, $payment->direction, $payment->description,
            ]));

        $this->sheet($writer, false, 'Recurring',
            ['Name', 'Type', 'Wallet', 'Counter Wallet', 'Category', 'Amount', 'Currency', 'Frequency', 'Interval', 'Next Run', 'Last Run', 'Active'],
            $recurring->map(fn (RecurringTransaction $item): array => [
                $item->name, $item->type->label(), $item->wallet->name, $item->counterWallet?->name,
                $item->category?->name, $item->amount / 100, $item->currency, $item->frequency->label(),
                $item->interval, $item->next_run_on->toDateString(), $item->last_run_on?->toDateString(),
                $item->is_active ? 'Yes' : 'No',
            ]));

        $writer->close();
    }

    /**
     * @param  list<string>  $headings
     * @param  Collection<int, list<mixed>>  $rows
     */
    private function sheet(Writer $writer, bool $first, string $title, array $headings, Collection $rows): void
    {
        $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($title);
        $writer->addRow(Row::fromValues($headings));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }
}
