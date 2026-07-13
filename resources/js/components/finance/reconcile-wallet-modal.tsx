import { Form, usePage } from '@inertiajs/react';
import Money from '@/components/finance/money';
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
import { useCurrency } from '@/hooks/use-currency';
import { reconcile } from '@/routes/wallets';
import type { Wallet } from '@/types';

type Props = {
    wallet: Wallet;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function ReconcileWalletModal({
    wallet,
    open,
    onOpenChange,
}: Props) {
    const { currentCompany } = usePage().props;
    const { symbol } = useCurrency();

    if (!currentCompany) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...reconcile.form({
                        current_company: currentCompany.slug,
                        wallet: wallet.id,
                    })}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    Reconcile {wallet.name}
                                </DialogTitle>
                                <DialogDescription>
                                    Book balance is{' '}
                                    <Money
                                        amount={wallet.balance}
                                        className="font-medium text-foreground"
                                    />
                                    . Enter the real balance from your bank/app
                                    statement — the difference is posted as a
                                    Balance Adjustment transaction.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="actual-balance">
                                    Actual balance ({symbol})
                                </Label>
                                <Input
                                    id="actual-balance"
                                    name="actual_balance"
                                    type="number"
                                    step="0.01"
                                    defaultValue={(
                                        wallet.balance / 100
                                    ).toFixed(2)}
                                    required
                                />
                                <InputError message={errors.actual_balance} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Reconcile
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
