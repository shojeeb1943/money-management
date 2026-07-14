import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    ChevronDown,
    MoreVertical,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import Money from '@/components/finance/money';
import TransactionFormSheet, {
    entryModeFor,
} from '@/components/finance/transaction-form-sheet';
import type { EntryMode } from '@/components/finance/transaction-form-sheet';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/date';
import { destroy, index } from '@/routes/transactions';
import type {
    SimplePagination,
    TransactionFilters,
    TransactionRow,
} from '@/types';

type Option = { id: number; name: string };
type CategoryOption = Option & {
    kind: 'income' | 'expense';
    parentId: number | null;
};

type Props = {
    transactions: TransactionRow[];
    pagination: SimplePagination;
    totals: { in: number; out: number; net: number };
    filters: TransactionFilters;
    wallets: Option[];
    categories: CategoryOption[];
};

const ALL = '__all__';

export default function TransactionsIndex({
    transactions,
    pagination,
    totals,
    filters,
    wallets,
    categories,
}: Props) {
    const { currentCompany } = usePage().props;
    const [sheetOpen, setSheetOpen] = useState(false);
    const [entryMode, setEntryMode] = useState<EntryMode>('expense');
    const [voiding, setVoiding] = useState<TransactionRow | null>(null);
    const [editing, setEditing] = useState<TransactionRow | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    if (!currentCompany) {
        return null;
    }

    const applyFilters = (next: Partial<TransactionFilters>) => {
        const merged = { ...filters, ...next };
        const params = Object.fromEntries(
            Object.entries(merged).filter(
                ([, value]) =>
                    value !== undefined && value !== '' && value !== ALL,
            ),
        );

        router.get(
            index.url({ current_company: currentCompany.slug }),
            params,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const openCreate = (mode: EntryMode) => {
        setEditing(null);
        setEntryMode(mode);
        setSheetOpen(true);
    };

    const openEdit = (transaction: TransactionRow) => {
        setEditing(transaction);
        setEntryMode(entryModeFor(transaction));
        setSheetOpen(true);
    };

    const voidTransaction = (transaction: TransactionRow) => {
        setVoiding(transaction);
    };

    const confirmVoid = () => {
        if (!voiding) {
            return;
        }

        router.delete(
            destroy.url({
                current_company: currentCompany.slug,
                transaction: voiding.id,
            }),
            {
                preserveScroll: true,
            },
        );
    };

    const pageUrl = (page: number) => {
        const params = new URLSearchParams(
            Object.entries(filters).filter(
                ([, value]) => value !== undefined && value !== '',
            ) as [string, string][],
        );
        params.set('page', String(page));

        return `${index.url({ current_company: currentCompany.slug })}?${params.toString()}`;
    };

    return (
        <>
            <Head title="Transactions" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Transactions"
                        description="Every money in and out"
                    />
                    <div className="flex gap-2">
                        <Button
                            onClick={() => openCreate('income')}
                            data-test="new-income-button"
                        >
                            <Plus /> Income entry
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => openCreate('expense')}
                            data-test="new-expense-button"
                        >
                            <Plus /> Expense entry
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    More <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onSelect={() => openCreate('transfer')}
                                >
                                    <ArrowLeftRight /> Transfer
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onSelect={() => openCreate('capital')}
                                >
                                    <Plus /> Capital entry
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters({ search });
                        }}
                        className="relative"
                    >
                        <Search className="absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search description or reference"
                            className="w-64 pl-8"
                        />
                    </form>

                    <Select
                        value={filters.type ?? ALL}
                        onValueChange={(value) => applyFilters({ type: value })}
                    >
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All types</SelectItem>
                            <SelectItem value="income">Income</SelectItem>
                            <SelectItem value="expense">Expense</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                            <SelectItem value="capital_withdrawal">
                                Capital withdrawal
                            </SelectItem>
                            <SelectItem value="capital_investment">
                                Capital investment
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.wallet ?? ALL}
                        onValueChange={(value) =>
                            applyFilters({ wallet: value })
                        }
                    >
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Wallet" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All wallets</SelectItem>
                            {wallets.map((wallet) => (
                                <SelectItem
                                    key={wallet.id}
                                    value={String(wallet.id)}
                                >
                                    {wallet.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.category ?? ALL}
                        onValueChange={(value) =>
                            applyFilters({ category: value })
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All categories</SelectItem>
                            {categories.map((category) => (
                                <SelectItem
                                    key={category.id}
                                    value={String(category.id)}
                                >
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Input
                        type="date"
                        value={filters.from ?? ''}
                        onChange={(event) =>
                            applyFilters({ from: event.target.value })
                        }
                        className="w-36"
                        aria-label="From date"
                    />
                    <Input
                        type="date"
                        value={filters.to ?? ''}
                        onChange={(event) =>
                            applyFilters({ to: event.target.value })
                        }
                        className="w-36"
                        aria-label="To date"
                    />

                    <Select
                        value={filters.status ?? 'posted'}
                        onValueChange={(value) =>
                            applyFilters({ status: value })
                        }
                    >
                        <SelectTrigger className="w-28">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="posted">Posted</SelectItem>
                            <SelectItem value="voided">Voided</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-muted-foreground">
                    <span>
                        In{' '}
                        <span className="font-medium text-emerald-600 dark:text-emerald-400">
                            <Money amount={totals.in} />
                        </span>
                    </span>
                    <span>
                        Out{' '}
                        <span className="font-medium text-red-600 dark:text-red-400">
                            <Money amount={totals.out} />
                        </span>
                    </span>
                    <span>
                        Net{' '}
                        <span
                            className={
                                totals.net < 0
                                    ? 'font-medium text-red-600 dark:text-red-400'
                                    : 'font-medium text-foreground'
                            }
                        >
                            <Money amount={totals.net} />
                        </span>
                    </span>
                    <span className="text-xs">
                        {pagination.total} transactions · transfers excluded
                        from totals
                    </span>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Wallet</TableHead>
                            <TableHead className="text-right">Amount</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {transactions.map((transaction) => (
                            <TableRow
                                key={transaction.id}
                                className={
                                    transaction.voided
                                        ? 'opacity-50'
                                        : undefined
                                }
                            >
                                <TableCell className="text-muted-foreground">
                                    {formatDate(transaction.date)}
                                </TableCell>
                                <TableCell>
                                    {transaction.description ??
                                        transaction.typeLabel}
                                    {transaction.reference ? (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            {transaction.reference}
                                        </span>
                                    ) : null}
                                    {transaction.voided ? (
                                        <Badge
                                            variant="secondary"
                                            className="ml-2"
                                        >
                                            Voided
                                        </Badge>
                                    ) : null}
                                </TableCell>
                                <TableCell>
                                    {transaction.type === 'transfer' ? (
                                        <span className="flex items-center gap-1 text-muted-foreground">
                                            <ArrowLeftRight className="size-3" />
                                            {transaction.walletName} →{' '}
                                            {transaction.counterWalletName}
                                        </span>
                                    ) : transaction.categoryName ? (
                                        <span className="flex items-center gap-2">
                                            <span
                                                className="size-2.5 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        transaction.categoryColor ??
                                                        '#475569',
                                                }}
                                            />
                                            {transaction.categoryName}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            {transaction.typeLabel}
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell>{transaction.walletName}</TableCell>
                                <TableCell className="text-right">
                                    {transaction.type === 'transfer' ? (
                                        <Money amount={transaction.amount} />
                                    ) : (
                                        <Money
                                            amount={transaction.signedAmount}
                                            colored
                                        />
                                    )}
                                </TableCell>
                                <TableCell>
                                    {!transaction.voided ? (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    aria-label="Transaction actions"
                                                >
                                                    <MoreVertical className="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {transaction.type !==
                                                'transfer' ? (
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            openEdit(
                                                                transaction,
                                                            )
                                                        }
                                                    >
                                                        <Pencil /> Edit
                                                    </DropdownMenuItem>
                                                ) : null}
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={() =>
                                                        voidTransaction(
                                                            transaction,
                                                        )
                                                    }
                                                >
                                                    <Trash2 /> Void
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    ) : null}
                                </TableCell>
                            </TableRow>
                        ))}

                        {transactions.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No transactions found.
                                </TableCell>
                            </TableRow>
                        ) : null}
                    </TableBody>
                </Table>

                {pagination.lastPage > 1 ? (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Page {pagination.currentPage} of{' '}
                            {pagination.lastPage} ({pagination.total}{' '}
                            transactions)
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

            <ConfirmDialog
                title="Void this transaction?"
                description="The journal entry is reversed and wallet balances are restored. Voided transactions stay visible in the voided filter."
                confirmLabel="Void transaction"
                open={voiding !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setVoiding(null);
                    }
                }}
                onConfirm={confirmVoid}
            />

            <TransactionFormSheet
                key={editing?.id ?? `create-${entryMode}`}
                transaction={editing}
                mode={entryMode}
                wallets={wallets}
                categories={categories}
                open={sheetOpen}
                onOpenChange={setSheetOpen}
            />
        </>
    );
}

TransactionsIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Transactions',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
