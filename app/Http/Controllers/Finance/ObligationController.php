<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Obligations\CreateObligation;
use App\Actions\Obligations\RecordPayment;
use App\Enums\ObligationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveObligationRequest;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\Wallet;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

final class ObligationController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        $obligations = Obligation::query()
            ->forCompany($current_company)
            ->with(['wallet', 'payments'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Obligation $obligation): array => [
                'id' => $obligation->id,
                'kind' => $obligation->kind->value,
                'kindLabel' => $obligation->kind->label(),
                'label' => $obligation->label,
                'walletId' => $obligation->wallet_id,
                'walletName' => $obligation->wallet->name,
                'amount' => $obligation->amount,
                'remaining' => $obligation->remaining,
                'currency' => $obligation->currency,
                'description' => $obligation->description,
                'status' => $obligation->status,
                'settled' => $obligation->isSettled(),
                'archived' => $obligation->isArchived(),
                'openedAt' => $obligation->created_at->toDateString(),
                'payments' => $obligation->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'direction' => $payment->direction,
                    'date' => $payment->date->toDateString(),
                    'description' => $payment->description,
                ])->values(),
            ]);

        return Inertia::render('obligations/index', [
            'obligations' => $obligations,
            'wallets' => Wallet::query()->active()->orderBy('name')->get(['id', 'name', 'currency'])
                ->map(fn (Wallet $wallet): array => ['id' => $wallet->id, 'name' => $wallet->name, 'currency' => $wallet->currency]),
            'kinds' => collect(ObligationKind::cases())->map(fn (ObligationKind $kind): array => [
                'value' => $kind->value,
                'label' => $kind->label(),
            ]),
        ]);
    }

    public function store(SaveObligationRequest $request, Company $current_company, CreateObligation $createObligation): RedirectResponse
    {
        $wallet = Wallet::query()->whereKey($request->validated('wallet_id'))->firstOrFail();

        $obligation = $createObligation->handle(
            $current_company,
            ObligationKind::from($request->validated('kind')),
            $request->validated('label'),
            $wallet,
            $request->validated('amount'),
            $request->validated('description'),
            $request->user(),
        );

        AuditLogger::log($current_company, $request->user(), 'created', $obligation, [
            'kind' => $obligation->kind->value,
            'amount' => $obligation->amount,
            'wallet' => $wallet->name,
            'transaction_id' => $obligation->transaction_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Obligation created.')]);

        return back();
    }

    public function pay(Request $request, Company $current_company, Obligation $obligation, RecordPayment $recordPayment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $before = ['remaining' => $obligation->remaining, 'status' => $obligation->status];

        $payment = $recordPayment->handle(
            $current_company,
            $obligation,
            $obligation->wallet,
            Money::toMinorUnits((string) $validated['amount']),
            Date::parse($validated['date']),
            $validated['description'],
            $request->user(),
        );

        AuditLogger::log($current_company, $request->user(), 'paid', $obligation, [
            'amount' => $payment->amount,
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'before' => $before,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded.')]);

        return back();
    }

    public function archive(Request $request, Company $current_company, Obligation $obligation): RedirectResponse
    {
        $before = $obligation->archived_at?->toJSON();

        $obligation->update([
            'archived_at' => $obligation->isArchived() ? null : now(),
        ]);

        AuditLogger::log($current_company, $request->user(), 'archived', $obligation, ['before' => $before]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $obligation->isArchived() ? __('Obligation archived.') : __('Obligation restored.'),
        ]);

        return back();
    }
}
