import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Scale } from 'lucide-react';
import { useState } from 'react';
import Money from '@/components/finance/money';
import ReconcileWalletModal from '@/components/finance/reconcile-wallet-modal';
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
import { formatDate } from '@/lib/date';
import { index, show } from '@/routes/wallets';
import type { LedgerRow, SimplePagination, Wallet } from '@/types';

type Props = {
    wallet: Wallet;
    ledger: LedgerRow[];
    pagination: SimplePagination;
};

export default function WalletShow({ wallet, ledger, pagination }: Props) {
    const { currentCompany } = usePage().props;
    const [reconcileOpen, setReconcileOpen] = useState(false);

    if (!currentCompany) {
        return null;
    }

    const pageUrl = (page: number) =>
        `${show.url({ current_company: currentCompany.slug, wallet: wallet.id })}?page=${page}`;

    return (
        <>
            <Head title={wallet.name} />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={index({
                                current_company: currentCompany.slug,
                            })}
                        >
                            <ArrowLeft className="size-4" /> Wallets
                        </Link>
                    </Button>
                </div>

                <div className="flex items-center justify-between">
                    <Heading
                        title={wallet.name}
                        description={wallet.typeLabel}
                    />
                    <div className="text-right">
                        <Button
                            variant="outline"
                            size="sm"
                            className="mb-2"
                            onClick={() => setReconcileOpen(true)}
                        >
                            <Scale className="size-4" /> Reconcile
                        </Button>
                        <p className="text-sm text-muted-foreground">
                            Current balance
                        </p>
                        <Money
                            amount={wallet.balance}
                            currency={wallet.currency}
                            colored
                            className="text-3xl font-semibold"
                        />
                        {wallet.archived ? (
                            <Badge variant="secondary">Archived</Badge>
                        ) : null}
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead className="text-right">In</TableHead>
                            <TableHead className="text-right">Out</TableHead>
                            <TableHead className="text-right">
                                Balance
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {ledger.map((row) => (
                            <TableRow key={row.id}>
                                <TableCell className="text-muted-foreground">
                                    {formatDate(row.date)}
                                </TableCell>
                                <TableCell>{row.description}</TableCell>
                                <TableCell className="text-right">
                                    {row.debit > 0 ? (
                                        <Money
                                            amount={row.debit}
                                            className="text-emerald-600 dark:text-emerald-400"
                                        />
                                    ) : null}
                                </TableCell>
                                <TableCell className="text-right">
                                    {row.credit > 0 ? (
                                        <Money
                                            amount={row.credit}
                                            className="text-red-600 dark:text-red-400"
                                        />
                                    ) : null}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Money amount={row.balance} />
                                </TableCell>
                            </TableRow>
                        ))}

                        {ledger.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No transactions in this wallet yet.
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
                                        href={pageUrl(
                                            pagination.currentPage - 1,
                                        )}
                                        preserveScroll
                                    >
                                        Previous
                                    </Link>
                                </Button>
                            ) : null}
                            {pagination.currentPage < pagination.lastPage ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={pageUrl(
                                            pagination.currentPage + 1,
                                        )}
                                        preserveScroll
                                    >
                                        Next
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    </div>
                ) : null}
            </div>

            <ReconcileWalletModal
                wallet={wallet}
                open={reconcileOpen}
                onOpenChange={setReconcileOpen}
            />
        </>
    );
}

WalletShow.layout = (props: { currentCompany?: { slug: string } | null }) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Wallets',
                  href: index({ current_company: props.currentCompany.slug }),
              },
              {
                  title:
                      (props as { wallet?: { name: string } }).wallet?.name ??
                      'Wallet',
                  href: '',
              },
          ]
        : [],
});
