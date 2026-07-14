import { Form, Head, router, usePage } from '@inertiajs/react';
import { Pause, Play, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import Money from '@/components/finance/money';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCurrency } from '@/hooks/use-currency';
import { formatDate, todayIn } from '@/lib/date';
import { destroy, index, store, toggle } from '@/routes/recurring';

type RecurringRow = {
    id: number;
    name: string;
    type: string;
    typeLabel: string;
    walletName: string;
    counterWalletName: string | null;
    categoryName: string | null;
    amount: number;
    frequency: string;
    frequencyLabel: string;
    interval: number;
    nextRunOn: string;
    lastRunOn: string | null;
    endsOn: string | null;
    active: boolean;
};

type Option = { id: number; name: string };
type CategoryOption = Option & {
    kind: 'income' | 'expense';
    parentId: number | null;
};

type Props = {
    recurring: RecurringRow[];
    wallets: Option[];
    categories: CategoryOption[];
    frequencies: Array<{ value: string; label: string }>;
};

export default function RecurringIndex({
    recurring,
    wallets,
    categories,
    frequencies,
}: Props) {
    const { currentCompany } = usePage().props;
    const { symbol } = useCurrency();
    const [sheetOpen, setSheetOpen] = useState(false);
    const [deleting, setDeleting] = useState<RecurringRow | null>(null);
    const [type, setType] = useState('expense');
    const [walletId, setWalletId] = useState(
        wallets[0] ? String(wallets[0].id) : '',
    );
    const [counterWalletId, setCounterWalletId] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [frequency, setFrequency] = useState('monthly');

    if (!currentCompany) {
        return null;
    }

    const slug = currentCompany.slug;
    const needsCategory = type === 'income' || type === 'expense';
    const kindCategories = categories.filter(
        (category) => category.kind === type,
    );

    return (
        <>
            <Head title="Recurring" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Recurring"
                        description="Rent, salaries and subscriptions posted automatically"
                    />
                    <Button onClick={() => setSheetOpen(true)}>
                        <Plus /> New recurring
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Wallet</TableHead>
                            <TableHead>Schedule</TableHead>
                            <TableHead>Next run</TableHead>
                            <TableHead className="text-right">Amount</TableHead>
                            <TableHead className="w-20" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {recurring.map((row) => (
                            <TableRow
                                key={row.id}
                                className={
                                    row.active ? undefined : 'opacity-50'
                                }
                            >
                                <TableCell className="font-medium">
                                    {row.name}
                                    {!row.active ? (
                                        <Badge
                                            variant="secondary"
                                            className="ml-2"
                                        >
                                            Paused
                                        </Badge>
                                    ) : null}
                                </TableCell>
                                <TableCell>
                                    {row.type === 'transfer'
                                        ? `${row.walletName} → ${row.counterWalletName}`
                                        : (row.categoryName ?? row.typeLabel)}
                                </TableCell>
                                <TableCell>{row.walletName}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    Every{' '}
                                    {row.interval > 1 ? `${row.interval} ` : ''}
                                    {row.frequencyLabel.toLowerCase()}
                                    {row.endsOn ? ` until ${row.endsOn}` : ''}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDate(row.nextRunOn)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Money amount={row.amount} />
                                </TableCell>
                                <TableCell>
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label={
                                                row.active ? 'Pause' : 'Resume'
                                            }
                                            onClick={() =>
                                                router.patch(
                                                    toggle.url({
                                                        current_company: slug,
                                                        recurring_transaction:
                                                            row.id,
                                                    }),
                                                    {},
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        >
                                            {row.active ? (
                                                <Pause className="size-4" />
                                            ) : (
                                                <Play className="size-4" />
                                            )}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Delete"
                                            onClick={() => setDeleting(row)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}

                        {recurring.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No recurring transactions yet.
                                </TableCell>
                            </TableRow>
                        ) : null}
                    </TableBody>
                </Table>
            </div>

            <ConfirmDialog
                title={`Delete "${deleting?.name}"?`}
                description="The schedule is removed. Transactions it already created stay in the books."
                confirmLabel="Delete schedule"
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                onConfirm={() => {
                    if (deleting) {
                        router.delete(
                            destroy.url({
                                current_company: slug,
                                recurring_transaction: deleting.id,
                            }),
                            { preserveScroll: true },
                        );
                    }
                }}
            />

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <Form
                        key={`${String(sheetOpen)}-${type}`}
                        {...store.form({ current_company: slug })}
                        className="flex h-full flex-col gap-5 p-4"
                        onSuccess={() => setSheetOpen(false)}
                    >
                        {({ errors, processing }) => (
                            <>
                                <SheetHeader className="p-0">
                                    <SheetTitle>
                                        New recurring transaction
                                    </SheetTitle>
                                    <SheetDescription>
                                        Posted automatically on schedule,
                                        starting from the start date.
                                    </SheetDescription>
                                </SheetHeader>

                                <div className="grid gap-2">
                                    <Label htmlFor="recurring-name">Name</Label>
                                    <Input
                                        id="recurring-name"
                                        name="name"
                                        placeholder="e.g. Office rent"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Type</Label>
                                    <Select
                                        value={type}
                                        onValueChange={(value) => {
                                            setType(value);
                                            setCategoryId('');
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="expense">
                                                Expense
                                            </SelectItem>
                                            <SelectItem value="income">
                                                Income
                                            </SelectItem>
                                            <SelectItem value="transfer">
                                                Transfer
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="type"
                                        value={type}
                                    />
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>
                                        {type === 'transfer'
                                            ? 'From wallet'
                                            : 'Wallet'}
                                    </Label>
                                    <Select
                                        value={walletId}
                                        onValueChange={setWalletId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select wallet" />
                                        </SelectTrigger>
                                        <SelectContent>
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
                                    <input
                                        type="hidden"
                                        name="wallet_id"
                                        value={walletId}
                                    />
                                    <InputError message={errors.wallet_id} />
                                </div>

                                {type === 'transfer' ? (
                                    <div className="grid gap-2">
                                        <Label>To wallet</Label>
                                        <Select
                                            value={counterWalletId}
                                            onValueChange={setCounterWalletId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select destination" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {wallets
                                                    .filter(
                                                        (wallet) =>
                                                            String(
                                                                wallet.id,
                                                            ) !== walletId,
                                                    )
                                                    .map((wallet) => (
                                                        <SelectItem
                                                            key={wallet.id}
                                                            value={String(
                                                                wallet.id,
                                                            )}
                                                        >
                                                            {wallet.name}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="counter_wallet_id"
                                            value={counterWalletId}
                                        />
                                        <InputError
                                            message={errors.counter_wallet_id}
                                        />
                                    </div>
                                ) : null}

                                {needsCategory ? (
                                    <div className="grid gap-2">
                                        <Label>Category</Label>
                                        <Select
                                            value={categoryId}
                                            onValueChange={setCategoryId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {kindCategories.map(
                                                    (category) => (
                                                        <SelectItem
                                                            key={category.id}
                                                            value={String(
                                                                category.id,
                                                            )}
                                                        >
                                                            {category.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="category_id"
                                            value={categoryId}
                                        />
                                        <InputError
                                            message={errors.category_id}
                                        />
                                    </div>
                                ) : null}

                                <div className="grid gap-2">
                                    <Label htmlFor="recurring-amount">
                                        Amount ({symbol})
                                    </Label>
                                    <Input
                                        id="recurring-amount"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label>Frequency</Label>
                                        <Select
                                            value={frequency}
                                            onValueChange={setFrequency}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {frequencies.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="frequency"
                                            value={frequency}
                                        />
                                        <InputError
                                            message={errors.frequency}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="recurring-interval">
                                            Every
                                        </Label>
                                        <Input
                                            id="recurring-interval"
                                            name="interval"
                                            type="number"
                                            min="1"
                                            max="12"
                                            defaultValue={1}
                                            required
                                        />
                                        <InputError message={errors.interval} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="recurring-starts">
                                            Starts on
                                        </Label>
                                        <Input
                                            id="recurring-starts"
                                            name="starts_on"
                                            type="date"
                                            defaultValue={todayIn(
                                                currentCompany?.timezone,
                                            )}
                                            required
                                        />
                                        <InputError
                                            message={errors.starts_on}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="recurring-ends">
                                            Ends on (optional)
                                        </Label>
                                        <Input
                                            id="recurring-ends"
                                            name="ends_on"
                                            type="date"
                                        />
                                        <InputError message={errors.ends_on} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="recurring-description">
                                        Description (optional)
                                    </Label>
                                    <Input
                                        id="recurring-description"
                                        name="description"
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <SheetFooter className="mt-auto flex-row justify-end gap-2 p-0">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setSheetOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        Create
                                    </Button>
                                </SheetFooter>
                            </>
                        )}
                    </Form>
                </SheetContent>
            </Sheet>
        </>
    );
}

RecurringIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Recurring',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
