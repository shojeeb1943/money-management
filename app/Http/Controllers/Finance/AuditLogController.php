<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Audit\RestoreAuditLog;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

final class AuditLogController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        $logs = AuditLog::query()
            ->forCompany($current_company)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50);

        return Inertia::render('audit/index', [
            'logs' => collect($logs->items())->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'userName' => $log->user->name ?? 'System',
                'action' => $log->action,
                'subjectType' => class_basename($log->auditable_type),
                'subjectId' => $log->auditable_id,
                'viaAi' => ($log->changes['via'] ?? null) === 'mcp',
                'summary' => $this->describe($log, $current_company->currency),
                'changes' => $log->changes,
                'createdAt' => $log->created_at->timezone($current_company->timezone)->format('M j, Y g:i A'),
                'restoredAt' => $log->restored_at?->timezone($current_company->timezone)->format('M j, Y g:i A'),
                'canRestore' => $this->canRestore($log),
            ]),
            'pagination' => [
                'currentPage' => $logs->currentPage(),
                'lastPage' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function restore(Request $request, Company $current_company, AuditLog $audit_log, RestoreAuditLog $restoreAuditLog): RedirectResponse
    {
        try {
            $restoreAuditLog->handle($audit_log, $request->user());
        } catch (InvalidArgumentException $invalidArgumentException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $invalidArgumentException->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change restored.')]);

        return back();
    }

    private function canRestore(AuditLog $log): bool
    {
        if ($log->restored_at !== null) {
            return false;
        }

        return match ($log->auditable_type) {
            Transaction::class => match ($log->action) {
                'created', 'voided' => true,
                'updated' => isset($log->changes['before']),
                'reconciled' => isset($log->changes['transaction_id']),
                default => false,
            },
            Obligation::class => match ($log->action) {
                'created' => true,
                'paid' => isset($log->changes['payment_id']) && array_key_exists('before', $log->changes ?? []),
                'archived' => array_key_exists('before', $log->changes ?? []),
                default => false,
            },
            default => false,
        };
    }

    private function describe(AuditLog $log, string $currency): string
    {
        try {
            return $this->buildSummary($log, $currency);
        } catch (Throwable) {
            return '—';
        }
    }

    private function buildSummary(AuditLog $log, string $currency): string
    {
        $changes = $log->changes ?? [];

        if ($log->action === 'restored') {
            return sprintf('Reversed a previous change (entry #%d)', $changes['reverses'] ?? 0);
        }

        return match (class_basename($log->auditable_type)) {
            'Transaction' => $this->describeTransaction($log->action, $changes, $currency),
            'Obligation' => $this->describeObligation($log->action, $changes, $currency),
            'Wallet' => $this->describeWallet($log->action, $changes, $currency),
            default => '—',
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function describeTransaction(string $action, array $changes, string $currency): string
    {
        return match ($action) {
            'created' => sprintf(
                '%s of %s in %s',
                TransactionType::from($changes['type'])->label(),
                Money::format($changes['amount'], $currency),
                $changes['wallet'],
            ),
            'voided' => sprintf(
                'Voided %s of %s',
                TransactionType::from($changes['type'])->label(),
                Money::format($changes['amount'], $currency),
            ),
            'updated' => isset($changes['amount']['from'], $changes['amount']['to']) && $changes['amount']['from'] !== $changes['amount']['to']
                ? sprintf(
                    'Amount changed from %s to %s',
                    Money::format($changes['amount']['from'], $currency),
                    Money::format($changes['amount']['to'], $currency),
                )
                : 'Transaction details updated',
            default => '—',
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function describeObligation(string $action, array $changes, string $currency): string
    {
        return match ($action) {
            'created' => sprintf(
                '%s of %s via %s',
                ObligationKind::from($changes['kind'])->label(),
                Money::format($changes['amount'], $currency),
                $changes['wallet'],
            ),
            'paid' => sprintf(
                'Payment of %s recorded (remaining %s → %s)',
                Money::format($changes['amount'], $currency),
                Money::format($changes['before']['remaining'] ?? 0, $currency),
                Money::format(($changes['before']['remaining'] ?? 0) - $changes['amount'], $currency),
            ),
            'archived' => ($changes['before'] ?? null) === null ? 'Archived' : 'Restored from archive',
            default => '—',
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function describeWallet(string $action, array $changes, string $currency): string
    {
        return match ($action) {
            'reconciled' => ($changes['transaction_id'] ?? null) !== null
                ? sprintf('Balance adjusted by %s', Money::format($changes['adjustment'] ?? 0, $currency))
                : 'Balance already matched — no adjustment',
            default => '—',
        };
    }
}
