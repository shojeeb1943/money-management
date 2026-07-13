import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import { CURRENCY_SYMBOLS, currencySymbol } from '@/lib/money';
import { store, update } from '@/routes/wallets';
import type { Wallet, WalletTypeOption } from '@/types';

const COLORS = [
    '#2563eb',
    '#16a34a',
    '#dc2626',
    '#ea580c',
    '#7c3aed',
    '#e2136e',
    '#f6921e',
    '#475569',
];

type Props = {
    wallet?: Wallet | null;
    walletTypes: WalletTypeOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function WalletFormModal({
    wallet,
    walletTypes,
    open,
    onOpenChange,
}: Props) {
    const { currentCompany } = usePage().props;
    const { currency: companyCurrency } = useCurrency();
    const [color, setColor] = useState(wallet?.color ?? COLORS[0]);
    const [type, setType] = useState(wallet?.type ?? walletTypes[0]?.value);
    const [currency, setCurrency] = useState(companyCurrency);

    if (!currentCompany) {
        return null;
    }

    const formProps = wallet
        ? update.form({
              current_company: currentCompany.slug,
              wallet: wallet.id,
          })
        : store.form({ current_company: currentCompany.slug });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...formProps}
                    className="space-y-6"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {wallet ? 'Edit wallet' : 'New wallet'}
                                </DialogTitle>
                                <DialogDescription>
                                    {wallet
                                        ? 'Update the wallet details.'
                                        : 'Add a money account like a bank, mobile wallet, card or cash.'}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="wallet-name">Name</Label>
                                <Input
                                    id="wallet-name"
                                    name="name"
                                    defaultValue={wallet?.name ?? ''}
                                    placeholder="e.g. Business Checking"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Type</Label>
                                <Select value={type} onValueChange={setType}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {walletTypes.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <input type="hidden" name="type" value={type} />
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="wallet-account-number">
                                    Account number (optional)
                                </Label>
                                <Input
                                    id="wallet-account-number"
                                    name="account_number"
                                    defaultValue={wallet?.accountNumber ?? ''}
                                />
                                <InputError message={errors.account_number} />
                            </div>

                            {!wallet ? (
                                <div className="grid gap-2">
                                    <Label>Currency</Label>
                                    <Select
                                        value={currency}
                                        onValueChange={setCurrency}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.keys(CURRENCY_SYMBOLS).map(
                                                (code) => (
                                                    <SelectItem
                                                        key={code}
                                                        value={code}
                                                    >
                                                        {code} (
                                                        {currencySymbol(
                                                            code,
                                                        ).trim()}
                                                        )
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="currency"
                                        value={currency}
                                    />
                                    <InputError message={errors.currency} />
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="wallet-opening-balance">
                                    Opening balance (
                                    {currencySymbol(
                                        wallet?.currency ?? currency,
                                    ).trim()}
                                    )
                                </Label>
                                <Input
                                    id="wallet-opening-balance"
                                    name="opening_balance"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    defaultValue={
                                        wallet
                                            ? (
                                                  wallet.openingBalance / 100
                                              ).toFixed(2)
                                            : undefined
                                    }
                                />
                                <InputError message={errors.opening_balance} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Color</Label>
                                <div className="flex gap-2">
                                    {COLORS.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            aria-label={`Color ${option}`}
                                            onClick={() => setColor(option)}
                                            className="size-6 rounded-full border-2"
                                            style={{
                                                backgroundColor: option,
                                                borderColor:
                                                    color === option
                                                        ? 'var(--foreground)'
                                                        : 'transparent',
                                            }}
                                        />
                                    ))}
                                </div>
                                <input
                                    type="hidden"
                                    name="color"
                                    value={color ?? ''}
                                />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {wallet ? 'Save changes' : 'Create wallet'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
