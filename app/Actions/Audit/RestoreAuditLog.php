<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Actions\Transactions\UnvoidTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Actions\Transactions\VoidTransaction;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RestoreAuditLog
{
    public function __construct(
        private VoidTransaction $voidTransaction,
        private UnvoidTransaction $unvoidTransaction,
        private UpdateTransaction $updateTransaction,
    ) {}

    public function handle(AuditLog $auditLog, User $actor): void
    {
        throw_if($auditLog->restored_at !== null, InvalidArgumentException::class, 'This change was already restored.');

        DB::transaction(function () use ($auditLog, $actor): void {
            $restored = match ($auditLog->auditable_type) {
                Transaction::class => $this->restoreTransaction($auditLog),
                Obligation::class => $this->restoreObligation($auditLog),
                default => throw new InvalidArgumentException('This change type cannot be restored.'),
            };

            $auditLog->update(['restored_at' => now(), 'restored_by' => $actor->id]);

            AuditLogger::log($auditLog->company, $actor, 'restored', $restored, ['reverses' => $auditLog->id]);
        });
    }

    private function restoreTransaction(AuditLog $auditLog): Transaction
    {
        return match ($auditLog->action) {
            'created' => $this->voidTransaction->handle($auditLog->auditable),
            'voided' => $this->unvoidTransaction->handle($auditLog->auditable),
            'updated' => $this->restoreUpdate($auditLog),
            'reconciled' => $this->restoreReconciliation($auditLog),
            default => throw new InvalidArgumentException('This change type cannot be restored.'),
        };
    }

    private function restoreObligation(AuditLog $auditLog): Model
    {
        return match ($auditLog->action) {
            'created' => $this->restoreObligationCreation($auditLog->auditable),
            'paid' => $this->restoreObligationPayment($auditLog),
            'archived' => $this->restoreObligationArchive($auditLog),
            default => throw new InvalidArgumentException('This change type cannot be restored.'),
        };
    }

    private function restoreObligationCreation(Obligation $obligation): Transaction
    {
        throw_if($obligation->payments()->exists(), InvalidArgumentException::class, 'Reverse its payments first.');
        throw_if($obligation->transaction === null, InvalidArgumentException::class, 'This change predates restore support.');

        $transaction = $this->voidTransaction->handle($obligation->transaction);

        $obligation->delete();

        return $transaction;
    }

    private function restoreObligationPayment(AuditLog $auditLog): Transaction
    {
        $payment = ObligationPayment::query()->find($auditLog->changes['payment_id'] ?? null);

        throw_if($payment === null, InvalidArgumentException::class, 'This payment no longer exists.');

        $obligation = $payment->obligation;
        $latest = $obligation->payments()->first();

        throw_unless($latest?->id === $payment->id, InvalidArgumentException::class, 'Restore payments in order, starting with the most recent.');

        $before = $auditLog->changes['before'] ?? null;

        throw_if($before === null, InvalidArgumentException::class, 'This change predates restore support.');
        throw_if($payment->transaction === null, InvalidArgumentException::class, 'This change predates restore support.');

        $transaction = $this->voidTransaction->handle($payment->transaction);

        $payment->delete();

        $obligation->update([
            'remaining' => $before['remaining'],
            'status' => $before['status'],
        ]);

        return $transaction;
    }

    private function restoreObligationArchive(AuditLog $auditLog): Obligation
    {
        $obligation = $auditLog->auditable;
        $before = $auditLog->changes['before'] ?? null;

        $obligation->update(['archived_at' => $before !== null ? Date::parse($before) : null]);

        return $obligation;
    }

    private function restoreUpdate(AuditLog $auditLog): Transaction
    {
        $before = $auditLog->changes['before'] ?? null;

        throw_if($before === null, InvalidArgumentException::class, 'This change predates restore support.');

        /** @var array{wallet_id: int, category_id: int|null, amount: int, date: string, description: string|null, reference: string|null} $before */
        $wallet = Wallet::query()->findOrFail($before['wallet_id']);
        $category = $before['category_id'] !== null
            ? Category::query()->find($before['category_id'])
            : null;

        return $this->updateTransaction->handle(
            $auditLog->auditable,
            $wallet,
            $before['amount'],
            Date::parse($before['date']),
            $category,
            $before['description'],
            $before['reference'],
        );
    }

    private function restoreReconciliation(AuditLog $auditLog): Transaction
    {
        $transactionId = $auditLog->changes['transaction_id'] ?? null;

        throw_if($transactionId === null, InvalidArgumentException::class, 'Nothing to restore for this reconciliation.');

        $transaction = Transaction::query()->forCompany($auditLog->company)->findOrFail($transactionId);

        return $this->voidTransaction->handle($transaction);
    }
}
