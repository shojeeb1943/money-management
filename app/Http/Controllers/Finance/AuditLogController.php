<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Audit\RestoreAuditLog;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Obligation;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

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
}
