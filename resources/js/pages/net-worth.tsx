import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeftRight } from 'lucide-react';
import { useState } from 'react';
import { store as storeCrossCompanyTransfer } from '@/actions/App/Http/Controllers/Finance/CrossCompanyTransferController';
import Money from '@/components/finance/money';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';

type CompanyWallet = {
    id: number;
    name: string;
    currency: string;
};

type CompanyRow = {
    id: number;
    name: string;
    slug: string;
    currency: string;
    totalCash: number;
    periodIncome: number;
    periodExpense: number;
    periodProfit: number;
    wallets: CompanyWallet[];
};

type Props = {
    companies: CompanyRow[];
    totalsByCurrency: Record<string, number>;
    from: string;
    to: string;
};

export default function NetWorthPage({
    companies,
    totalsByCurrency,
    from,
    to,
}: Props) {
    const [transferOpen, setTransferOpen] = useState(false);
    const [fromWalletId, setFromWalletId] = useState<string>('');
    const [fromCompanyId, setFromCompanyId] = useState<string>('');
    const [toWalletId, setToWalletId] = useState<string>('');
    const [toCompanyId, setToCompanyId] = useState<string>('');

    const selectFromWallet = (value: string) => {
        const [companyId, walletId] = value.split(':');
        setFromCompanyId(companyId);
        setFromWalletId(walletId);
    };

    const selectToWallet = (value: string) => {
        const [companyId, walletId] = value.split(':');
        setToCompanyId(companyId);
        setToWalletId(walletId);
    };

    const walletCount = companies.reduce(
        (count, company) => count + company.wallets.length,
        0,
    );

    return (
        <>
            <Head title="Net Worth" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Net Worth"
                        description={`Combined across all companies · this month (${from} to ${to})`}
                    />
                    <Dialog open={transferOpen} onOpenChange={setTransferOpen}>
                        <DialogTrigger asChild>
                            <Button disabled={walletCount < 2}>
                                <ArrowLeftRight /> Transfer between companies
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                key={String(transferOpen)}
                                {...storeCrossCompanyTransfer.form()}
                                className="space-y-6"
                                onSuccess={() => setTransferOpen(false)}
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Transfer between companies
                                            </DialogTitle>
                                            <DialogDescription>
                                                Move money from a wallet in
                                                one company to a wallet in
                                                another.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div className="grid gap-2">
                                            <Label>From</Label>
                                            <Select
                                                value={
                                                    fromCompanyId &&
                                                    fromWalletId
                                                        ? `${fromCompanyId}:${fromWalletId}`
                                                        : ''
                                                }
                                                onValueChange={
                                                    selectFromWallet
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select a wallet" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {companies.map(
                                                        (company) => (
                                                            <SelectGroup
                                                                key={
                                                                    company.id
                                                                }
                                                            >
                                                                <SelectLabel>
                                                                    {
                                                                        company.name
                                                                    }
                                                                </SelectLabel>
                                                                {company.wallets.map(
                                                                    (
                                                                        wallet,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                wallet.id
                                                                            }
                                                                            value={`${company.id}:${wallet.id}`}
                                                                        >
                                                                            {
                                                                                wallet.name
                                                                            }{' '}
                                                                            (
                                                                            {
                                                                                wallet.currency
                                                                            }

                                                                            )
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectGroup>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <input
                                                type="hidden"
                                                name="from_wallet_id"
                                                value={fromWalletId}
                                            />
                                            <input
                                                type="hidden"
                                                name="from_company_id"
                                                value={fromCompanyId}
                                            />
                                            <InputError
                                                message={
                                                    errors.from_wallet_id ??
                                                    errors.from_company_id
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>To</Label>
                                            <Select
                                                value={
                                                    toCompanyId && toWalletId
                                                        ? `${toCompanyId}:${toWalletId}`
                                                        : ''
                                                }
                                                onValueChange={
                                                    selectToWallet
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select a wallet" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {companies.map(
                                                        (company) => (
                                                            <SelectGroup
                                                                key={
                                                                    company.id
                                                                }
                                                            >
                                                                <SelectLabel>
                                                                    {
                                                                        company.name
                                                                    }
                                                                </SelectLabel>
                                                                {company.wallets.map(
                                                                    (
                                                                        wallet,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                wallet.id
                                                                            }
                                                                            value={`${company.id}:${wallet.id}`}
                                                                        >
                                                                            {
                                                                                wallet.name
                                                                            }{' '}
                                                                            (
                                                                            {
                                                                                wallet.currency
                                                                            }

                                                                            )
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectGroup>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <input
                                                type="hidden"
                                                name="to_wallet_id"
                                                value={toWalletId}
                                            />
                                            <input
                                                type="hidden"
                                                name="to_company_id"
                                                value={toCompanyId}
                                            />
                                            <InputError
                                                message={
                                                    errors.to_wallet_id ??
                                                    errors.to_company_id
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="transfer-amount">
                                                Amount
                                            </Label>
                                            <Input
                                                id="transfer-amount"
                                                name="amount"
                                                type="number"
                                                step="0.01"
                                                placeholder="0.00"
                                                required
                                            />
                                            <InputError
                                                message={errors.amount}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="transfer-date">
                                                Date
                                            </Label>
                                            <Input
                                                id="transfer-date"
                                                name="date"
                                                type="date"
                                                defaultValue={new Date()
                                                    .toISOString()
                                                    .slice(0, 10)}
                                                required
                                            />
                                            <InputError
                                                message={errors.date}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="transfer-description">
                                                Description (optional)
                                            </Label>
                                            <Input
                                                id="transfer-description"
                                                name="description"
                                                placeholder="e.g. Owner draw"
                                            />
                                            <InputError
                                                message={errors.description}
                                            />
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                disabled={
                                                    processing ||
                                                    !fromWalletId ||
                                                    !toWalletId
                                                }
                                            >
                                                Transfer
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Total cash across all companies</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        {Object.entries(totalsByCurrency).map(
                            ([currency, amount]) => (
                                <div
                                    key={currency}
                                    className="flex items-center justify-between text-lg font-semibold"
                                >
                                    <span>{currency}</span>
                                    <Money
                                        amount={amount}
                                        currency={currency}
                                    />
                                </div>
                            ),
                        )}
                        {companies.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No companies yet.
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2">
                    {companies.map((company) => (
                        <Card key={company.id}>
                            <CardHeader>
                                <CardTitle>
                                    <Link
                                        href={dashboard(company.slug)}
                                        className="hover:underline"
                                    >
                                        {company.name}
                                    </Link>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        Cash on hand
                                    </span>
                                    <Money
                                        amount={company.totalCash}
                                        currency={company.currency}
                                        className="font-semibold"
                                    />
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        This month income
                                    </span>
                                    <Money
                                        amount={company.periodIncome}
                                        currency={company.currency}
                                    />
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        This month expense
                                    </span>
                                    <Money
                                        amount={company.periodExpense}
                                        currency={company.currency}
                                    />
                                </div>
                                <div className="flex items-center justify-between text-sm font-medium">
                                    <span>This month profit</span>
                                    <Money
                                        amount={company.periodProfit}
                                        currency={company.currency}
                                        colored
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
