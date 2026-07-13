import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
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
import { useCurrency } from '@/hooks/use-currency';
import { todayIn } from '@/lib/date';
import {
    store as transactionStore,
    update as transactionUpdate,
} from '@/routes/transactions';
import { store as transferStore } from '@/routes/transfers';
import type { TransactionRow } from '@/types';

type Option = { id: number; name: string };
type CategoryOption = Option & {
    kind: 'income' | 'expense';
    parentId: number | null;
};

export type EntryMode = 'income' | 'expense' | 'transfer' | 'capital';

type Props = {
    transaction?: TransactionRow | null;
    mode: EntryMode;
    wallets: Option[];
    categories: CategoryOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const TITLES: Record<EntryMode, { create: string; description: string }> = {
    income: {
        create: 'New income entry',
        description: 'Money coming into a wallet.',
    },
    expense: {
        create: 'New expense entry',
        description: 'Money going out of a wallet.',
    },
    transfer: {
        create: 'New transfer',
        description: 'Move money between your own wallets.',
    },
    capital: {
        create: 'New capital entry',
        description: 'Money invested into or withdrawn from the business.',
    },
};

export function entryModeFor(transaction: TransactionRow): EntryMode {
    if (transaction.type === 'transfer') {
        return 'transfer';
    }

    if (
        transaction.type === 'capital_withdrawal' ||
        transaction.type === 'capital_investment'
    ) {
        return 'capital';
    }

    return transaction.type;
}

export default function TransactionFormSheet({
    transaction,
    mode,
    wallets,
    categories,
    open,
    onOpenChange,
}: Props) {
    const { currentCompany } = usePage().props;
    const { symbol } = useCurrency();
    const [capitalType, setCapitalType] = useState(
        transaction?.type === 'capital_investment'
            ? 'capital_investment'
            : 'capital_withdrawal',
    );
    const [walletId, setWalletId] = useState(
        transaction
            ? String(transaction.walletId)
            : wallets[0]
              ? String(wallets[0].id)
              : '',
    );
    const [counterWalletId, setCounterWalletId] = useState(
        transaction?.counterWalletId ? String(transaction.counterWalletId) : '',
    );
    const [categoryId, setCategoryId] = useState(
        transaction?.categoryId ? String(transaction.categoryId) : '',
    );

    if (!currentCompany) {
        return null;
    }

    const editing = Boolean(transaction);
    const isTransfer = mode === 'transfer';
    const needsCategory = mode === 'income' || mode === 'expense';

    const type = isTransfer
        ? 'transfer'
        : mode === 'capital'
          ? capitalType
          : mode;

    const formProps = transaction
        ? transactionUpdate.form({
              current_company: currentCompany.slug,
              transaction: transaction.id,
          })
        : isTransfer
          ? transferStore.form({ current_company: currentCompany.slug })
          : transactionStore.form({ current_company: currentCompany.slug });

    const kindCategories = categories.filter(
        (category) => category.kind === mode,
    );
    const parents = kindCategories.filter(
        (category) => category.parentId === null,
    );

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto sm:max-w-md">
                <Form
                    key={String(open)}
                    {...formProps}
                    className="flex h-full flex-col gap-6 p-4"
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <SheetHeader className="p-0">
                                <SheetTitle>
                                    {editing
                                        ? `Edit ${transaction?.typeLabel.toLowerCase()}`
                                        : TITLES[mode].create}
                                </SheetTitle>
                                <SheetDescription>
                                    {editing
                                        ? 'The old journal entry is voided and a fresh one is posted.'
                                        : TITLES[mode].description}
                                </SheetDescription>
                            </SheetHeader>

                            {!isTransfer ? (
                                <input type="hidden" name="type" value={type} />
                            ) : null}

                            {mode === 'capital' && !editing ? (
                                <div className="grid gap-2">
                                    <Label>Direction</Label>
                                    <Select
                                        value={capitalType}
                                        onValueChange={setCapitalType}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="capital_investment">
                                                Investment (money in)
                                            </SelectItem>
                                            <SelectItem value="capital_withdrawal">
                                                Withdrawal (money out)
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label>
                                    {isTransfer ? 'From wallet' : 'Wallet'}
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

                            {isTransfer ? (
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
                                                        String(wallet.id) !==
                                                        walletId,
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
                                            {parents.map((parent) => {
                                                const children =
                                                    kindCategories.filter(
                                                        (category) =>
                                                            category.parentId ===
                                                            parent.id,
                                                    );

                                                if (children.length === 0) {
                                                    return (
                                                        <SelectItem
                                                            key={parent.id}
                                                            value={String(
                                                                parent.id,
                                                            )}
                                                        >
                                                            {parent.name}
                                                        </SelectItem>
                                                    );
                                                }

                                                return (
                                                    <SelectGroup
                                                        key={parent.id}
                                                    >
                                                        <SelectLabel>
                                                            {parent.name}
                                                        </SelectLabel>
                                                        <SelectItem
                                                            value={String(
                                                                parent.id,
                                                            )}
                                                        >
                                                            {parent.name}
                                                        </SelectItem>
                                                        {children.map(
                                                            (child) => (
                                                                <SelectItem
                                                                    key={
                                                                        child.id
                                                                    }
                                                                    value={String(
                                                                        child.id,
                                                                    )}
                                                                >
                                                                    {'  '}
                                                                    {child.name}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value={categoryId}
                                    />
                                    <InputError message={errors.category_id} />
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="transaction-amount">
                                    Amount ({symbol})
                                </Label>
                                <Input
                                    id="transaction-amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    defaultValue={
                                        transaction
                                            ? (
                                                  transaction.amount / 100
                                              ).toFixed(2)
                                            : ''
                                    }
                                    placeholder="0.00"
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="transaction-date">Date</Label>
                                <Input
                                    id="transaction-date"
                                    name="date"
                                    type="date"
                                    defaultValue={
                                        transaction?.date ??
                                        todayIn(currentCompany?.timezone)
                                    }
                                    required
                                />
                                <InputError message={errors.date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="transaction-description">
                                    Description (optional)
                                </Label>
                                <Input
                                    id="transaction-description"
                                    name="description"
                                    defaultValue={
                                        transaction?.description ?? ''
                                    }
                                    placeholder="e.g. July sales income"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="transaction-reference">
                                    Reference (optional)
                                </Label>
                                <Input
                                    id="transaction-reference"
                                    name="reference"
                                    defaultValue={transaction?.reference ?? ''}
                                    placeholder="e.g. TRX-1234"
                                />
                                <InputError message={errors.reference} />
                            </div>

                            <SheetFooter className="mt-auto flex-row justify-end gap-2 p-0">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {editing ? 'Save changes' : 'Record'}
                                </Button>
                            </SheetFooter>
                        </>
                    )}
                </Form>
            </SheetContent>
        </Sheet>
    );
}
