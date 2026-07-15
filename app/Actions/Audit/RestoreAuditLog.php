<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Actions\Transactions\UnvoidTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Actions\Transactions\VoidTransaction;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\AuditLogger;
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
            $transaction = match ($auditLog->action) {
                'created' => $this->voidTransaction->handle($auditLog->auditable),
                'voided' => $this->unvoidTransaction->handle($auditLog->auditable),
                'updated' => $this->restoreUpdate($auditLog),
                'reconciled' => $this->restoreReconciliation($auditLog),
                default => throw new InvalidArgumentException('This change type cannot be restored.'),
            };

            $auditLog->update(['restored_at' => now(), 'restored_by' => $actor->id]);

            AuditLogger::log($auditLog->company, $actor, 'restored', $transaction, ['reverses' => $auditLog->id]);
        });
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
