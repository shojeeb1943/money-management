import { Head, router, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, HandCoins, HandHeart, Landmark, Plus } from 'lucide-react';
import { type ChangeEvent, useState } from 'react';
import { formatMoney } from '@/lib/money';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Wallet = { id: number; name: string; currency: string };
type Kind = { value: string; label: string };

type Payment = {
    id: number;
    amount: number;
    direction: string;
    date: string;
    description: string | null;
};

type Obligation = {
    id: number;
    kind: string;
    kindLabel: string;
    label: string;
    walletId: number;
    walletName: string;
    amount: number;
    remaining: number;
    currency: string;
    description: string | null;
    status: string;
    settled: boolean;
    archived: boolean;
    payments: Payment[];
};

type Props = {
    obligations: Obligation[];
    wallets: Wallet[];
    kinds: Kind[];
};

function routeUrl(path: string, companySlug: string, extra?: Record<string, string | number>): string {
    let url = `/${companySlug}${path}`;

    if (extra) {
        for (const [key, value] of Object.entries(extra)) {
            url = url.replace(`{${key}}`, String(value));
        }
    }

    return url;
}

export default function ObligationsIndex({ obligations, wallets, kinds }: Props) {
    const { currentCompany } = usePage().props;
    const [activeTab, setActiveTab] = useState('loan');
    const [newOpen, setNewOpen] = useState(false);
    const [payTarget, setPayTarget] = useState<Obligation | null>(null);

    if (!currentCompany) {
        return null;
    }

    const companySlug = currentCompany.slug;

    const filtered = obligations.filter(
        (o) => o.kind === activeTab && !o.archived,
    );
    const archived = obligations.filter(
        (o) => o.kind === activeTab && o.archived,
    );

    const activeSettled = filtered.filter((o) => o.settled);
    const activeActive = filtered.filter((o) => !o.settled);

    const totalRemaining = activeActive.reduce((s, o) => s + o.remaining, 0);

    const toggleArchive = (obligation: Obligation) => {
        router.patch(
            routeUrl('/obligations/{obligation}/archive', companySlug, { obligation: obligation.id }),
            {},
            { preserveScroll: true },
        );
    };

    const renderTable = (rows: Obligation[], settled: boolean) => {
        if (rows.length === 0) {
            return null;
        }

        return (
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        {settled ? 'Settled' : 'Active'}
                    </CardTitle>
                    {!settled ? (
                        <span className="text-sm text-muted-foreground">
                            Total remaining:{' '}
                            {formatMoney(totalRemaining, rows[0]?.currency)}
                        </span>
                    ) : null}
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-2 pr-4 font-medium">Who</th>
                                    <th className="py-2 pr-4 font-medium">Total</th>
                                    <th className="py-2 pr-4 font-medium">Remaining</th>
                                    <th className="py-2 pr-4 font-medium">Wallet</th>
                                    <th className="py-2 pr-4 font-medium">Status</th>
                                    <th className="py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((o) => (
                                    <tr key={o.id} className="border-b last:border-0 hover:bg-muted/50">
                                        <td className="py-2 pr-4">
                                            <div>{o.label}</div>
                                            {o.description ? (
                                                <div className="text-xs text-muted-foreground">
                                                    {o.description}
                                                </div>
                                            ) : null}
                                        </td>
                                        <td className="py-2 pr-4">
                                            {formatMoney(o.amount, o.currency)}
                                        </td>
                                        <td className="py-2 pr-4">
                                            {formatMoney(o.remaining, o.currency)}
                                        </td>
                                        <td className="py-2 pr-4">{o.walletName}</td>
                                        <td className="py-2 pr-4">
                                            <Badge
                                                variant={o.settled ? 'secondary' : 'default'}
                                                className="text-xs"
                                            >
                                                {o.settled ? 'settled' : 'active'}
                                            </Badge>
                                        </td>
                                        <td className="py-2 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        ···
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    {!o.settled ? (
                                                        <DropdownMenuItem
                                                            onSelect={() => setPayTarget(o)}
                                                        >
                                                            <HandCoins /> Record Payment
                                                        </DropdownMenuItem>
                                                    ) : null}
                                                    <DropdownMenuItem
                                                        onSelect={() => toggleArchive(o)}
                                                    >
                                                        {o.archived ? (
                                                            <>
                                                                <ArchiveRestore /> Restore
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Archive /> Archive
                                                            </>
                                                        )}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        );
    };

    return (
        <>
            <Head title="Obligations" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Obligations"
                        description="Track loans, lends, and money you're holding for others"
                    />
                    <Button onClick={() => setNewOpen(true)}>
                        <Plus /> New
                    </Button>
                </div>

                <div className="flex gap-1 rounded-lg bg-muted p-1">
                    {kinds.map((k) => (
                        <button
                            key={k.value}
                            type="button"
                            onClick={() => setActiveTab(k.value)}
                            className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                activeTab === k.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {k.label}
                        </button>
                    ))}
                </div>

                <div className="space-y-4">
                    {activeActive.length === 0 && activeSettled.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                            No {activeTab} obligations yet.
                        </p>
                    ) : (
                        <>
                            {renderTable(activeActive, false)}
                            {renderTable(activeSettled, true)}
                        </>
                    )}
                </div>

                {archived.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Archived</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">Who</th>
                                            <th className="py-2 pr-4 font-medium">Total</th>
                                            <th className="py-2 pr-4 font-medium">Remaining</th>
                                            <th className="py-2 pr-4 font-medium">Wallet</th>
                                            <th className="py-2" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {archived.map((o) => (
                                            <tr key={o.id} className="border-b last:border-0 hover:bg-muted/50">
                                                <td className="py-2 pr-4">{o.label}</td>
                                                <td className="py-2 pr-4">
                                                    {formatMoney(o.amount, o.currency)}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {formatMoney(o.remaining, o.currency)}
                                                </td>
                                                <td className="py-2 pr-4">{o.walletName}</td>
                                                <td className="py-2 text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => toggleArchive(o)}
                                                    >
                                                        <ArchiveRestore /> Restore
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : null}
            </div>

            <NewObligationModal
                open={newOpen}
                onOpenChange={setNewOpen}
                wallets={wallets}
                kinds={kinds}
                defaultKind={activeTab}
                companySlug={companySlug}
            />

            <PayModal
                obligation={payTarget}
                onClose={() => setPayTarget(null)}
                companySlug={companySlug}
            />
        </>
    );
}

function NewObligationModal({
    open,
    onOpenChange,
    wallets,
    kinds,
    defaultKind,
    companySlug,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    wallets: Wallet[];
    kinds: Kind[];
    defaultKind: string;
    companySlug: string;
}) {
    const [kind, setKind] = useState(defaultKind);
    const [label, setLabel] = useState('');
    const [amount, setAmount] = useState('');
    const [walletId, setWalletId] = useState('');
    const [description, setDescription] = useState('');

    const submit = () => {
        router.post(
            routeUrl('/obligations', companySlug),
            { kind, label, amount, wallet_id: walletId, description },
            { preserveScroll: true, onSuccess: () => onOpenChange(false) },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Obligation</DialogTitle>
                    <DialogDescription>
                        Record a loan, lend, or safekeeping.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div>
                        <Label>Kind</Label>
                        <Select value={kind} onValueChange={setKind}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {kinds.map((k) => (
                                    <SelectItem key={k.value} value={k.value}>
                                        {k.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label>Name</Label>
                        <Input
                            value={label}
                            onChange={(e: ChangeEvent<HTMLInputElement>) => setLabel(e.target.value)}
                            placeholder="Who is this with?"
                        />
                    </div>

                    <div>
                        <Label>Amount</Label>
                        <Input
                            value={amount}
                            onChange={(e: ChangeEvent<HTMLInputElement>) => setAmount(e.target.value)}
                            placeholder="0.00"
                        />
                    </div>

                    <div>
                        <Label>Wallet</Label>
                        <Select value={walletId} onValueChange={setWalletId}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select wallet..." />
                            </SelectTrigger>
                            <SelectContent>
                                {wallets.map((w) => (
                                    <SelectItem key={w.id} value={String(w.id)}>
                                        {w.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label>Note (optional)</Label>
                        <textarea
                            value={description}
                            onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setDescription(e.target.value)}
                            className="min-h-20 w-full rounded-md border border-input bg-transparent p-3 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            rows={3}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button onClick={submit} disabled={!label || !amount || !walletId}>
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function PayModal({
    obligation,
    onClose,
    companySlug,
}: {
    obligation: Obligation | null;
    onClose: () => void;
    companySlug: string;
}) {
    const [amount, setAmount] = useState('');
    const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
    const [description, setDescription] = useState('');

    if (!obligation) {
        return null;
    }

    const submit = () => {
        router.post(
            routeUrl('/obligations/{obligation}/pay', companySlug, { obligation: obligation.id }),
            { amount, date, description },
            { preserveScroll: true, onSuccess: () => onClose() },
        );
    };

    return (
        <Dialog open onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Record Payment — {obligation.label}</DialogTitle>
                    <DialogDescription>
                        Remaining: {formatMoney(obligation.remaining, obligation.currency)}{' '}
                        / {formatMoney(obligation.amount, obligation.currency)}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div>
                        <Label>Amount</Label>
                        <Input
                            value={amount}
                            onChange={(e: ChangeEvent<HTMLInputElement>) => setAmount(e.target.value)}
                            placeholder="0.00"
                        />
                    </div>

                    <div>
                        <Label>Date</Label>
                        <Input
                            type="date"
                            value={date}
                            onChange={(e: ChangeEvent<HTMLInputElement>) => setDate(e.target.value)}
                        />
                    </div>

                    <div>
                        <Label>Note (optional)</Label>
                        <textarea
                            value={description}
                            onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setDescription(e.target.value)}
                            className="min-h-20 w-full rounded-md border border-input bg-transparent p-3 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            rows={3}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button onClick={submit} disabled={!amount}>
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

ObligationsIndex.layout = () => ({
    breadcrumbs: [
        { title: 'Obligations', href: '/obligations' },
    ],
});
