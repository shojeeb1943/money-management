import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, restore } from '@/routes/audit';
import type { SimplePagination } from '@/types';

type LogRow = {
    id: number;
    userName: string;
    action: string;
    subjectType: string;
    subjectId: number;
    viaAi: boolean;
    summary: string;
    changes: Record<string, unknown> | null;
    createdAt: string;
    restoredAt: string | null;
    canRestore: boolean;
};

type Props = {
    logs: LogRow[];
    pagination: SimplePagination;
};

export default function AuditIndex({ logs, pagination }: Props) {
    const { currentCompany } = usePage().props;
    const [restoring, setRestoring] = useState<LogRow | null>(null);

    if (!currentCompany) {
        return null;
    }

    const confirmRestore = () => {
        if (!restoring) {
            return;
        }

        router.post(
            restore.url({
                current_company: currentCompany.slug,
                audit_log: restoring.id,
            }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Audit log" />

            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    title="Audit log"
                    description="Who changed what, and when"
                />

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>When</TableHead>
                            <TableHead>Who</TableHead>
                            <TableHead>Action</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead>Details</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.map((log) => (
                            <TableRow key={log.id}>
                                <TableCell className="whitespace-nowrap text-muted-foreground">
                                    {log.createdAt}
                                </TableCell>
                                <TableCell>
                                    {log.userName}
                                    {log.viaAi ? (
                                        <Badge
                                            variant="outline"
                                            className="ml-2"
                                        >
                                            AI
                                        </Badge>
                                    ) : null}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {log.action}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {log.subjectType} #{log.subjectId}
                                </TableCell>
                                <TableCell
                                    className="max-w-md truncate text-sm text-muted-foreground"
                                    title={
                                        log.changes
                                            ? JSON.stringify(log.changes)
                                            : undefined
                                    }
                                >
                                    {log.summary}
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap">
                                    {log.restoredAt ? (
                                        <Badge variant="outline">
                                            Restored
                                        </Badge>
                                    ) : log.canRestore ? (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setRestoring(log)}
                                        >
                                            Restore
                                        </Button>
                                    ) : null}
                                </TableCell>
                            </TableRow>
                        ))}

                        {logs.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No activity recorded yet.
                                </TableCell>
                            </TableRow>
                        ) : null}
                    </TableBody>
                </Table>

                {pagination.lastPage > 1 ? (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Page {pagination.currentPage} of{' '}
                            {pagination.lastPage} ({pagination.total} entries)
                        </p>
                        <div className="flex gap-2">
                            {pagination.currentPage > 1 ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={`${index.url({ current_company: currentCompany.slug })}?page=${pagination.currentPage - 1}`}
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            ) : null}
                            {pagination.currentPage < pagination.lastPage ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={`${index.url({ current_company: currentCompany.slug })}?page=${pagination.currentPage + 1}`}
                                    >
                                        Next
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    </div>
                ) : null}
            </div>

            <ConfirmDialog
                title="Restore this change?"
                description="This reverses the change and posts a new audit entry for the restore itself."
                confirmLabel="Restore"
                open={restoring !== null}
                onOpenChange={(open) => !open && setRestoring(null)}
                onConfirm={confirmRestore}
            />
        </>
    );
}

AuditIndex.layout = (props: { currentCompany?: { slug: string } | null }) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Audit Log',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
