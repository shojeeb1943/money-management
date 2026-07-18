import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    Banknote,
    CreditCard,
    HandCoins,
    Landmark,
    MoreVertical,
    Pencil,
    PiggyBank,
    Plus,
    PlusCircle,
    Smartphone,
} from 'lucide-react';
import { useState } from 'react';
import Money from '@/components/finance/money';
import TransactionFormSheet from '@/components/finance/transaction-form-sheet';
import WalletFormModal from '@/components/finance/wallet-form-modal';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { archive, index, show } from '@/routes/wallets';
import type { Wallet, WalletTypeOption } from '@/types';

type CategoryOption = {
    id: number;
    name: string;
    kind: 'income' | 'expense';
    parentId: number | null;
};

const TYPE_ICONS: Record<string, typeof Landmark> = {
    bank: Landmark,
    mobile_banking: Smartphone,
    cash: Banknote,
    card: CreditCard,
    savings: PiggyBank,
    loan: HandCoins,
};

type Props = {
    wallets: Wallet[];
    walletTypes: WalletTypeOption[];
    categories: CategoryOption[];
};

export default function WalletsIndex({ wallets, walletTypes, categories }: Props) {
    const { currentCompany } = usePage().props;
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Wallet | null>(null);
    const [addMoneyWallet, setAddMoneyWallet] = useState<Wallet | null>(null);

    if (!currentCompany) {
        return null;
    }

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (wallet: Wallet) => {
        setEditing(wallet);
        setFormOpen(true);
    };

    const toggleArchive = (wallet: Wallet) => {
        router.patch(
            archive.url({
                current_company: currentCompany.slug,
                wallet: wallet.id,
            }),
            {},
            { preserveScroll: true },
        );
    };

    const total = wallets
        .filter((wallet) => !wallet.archived && wallet.currency === currentCompany?.currency)
        .reduce((sum, wallet) => sum + wallet.balance, 0);

    return (
        <>
            <Head title="Wallets" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Wallets"
                        description="Your money accounts and their live balances"
                    />
                    <Button onClick={openCreate} data-test="new-wallet-button">
                        <Plus /> New wallet
                    </Button>
                </div>

                <div className="text-sm text-muted-foreground">
                    Total across active {currentCompany?.currency ?? 'BDT'} wallets:{' '}
                    <Money
                        amount={total}
                        className="font-semibold text-foreground"
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {wallets.map((wallet) => {
                        const Icon = TYPE_ICONS[wallet.type] ?? Landmark;

                        return (
                            <Card
                                key={wallet.id}
                                className={
                                    wallet.archived ? 'opacity-60' : undefined
                                }
                            >
                                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="flex size-8 items-center justify-center rounded-full text-white"
                                            style={{
                                                backgroundColor:
                                                    wallet.color ?? '#475569',
                                            }}
                                        >
                                            <Icon className="size-4" />
                                        </span>
                                        <div>
                                            <CardTitle className="text-base">
                                                <Link
                                                    href={show({
                                                        current_company:
                                                            currentCompany.slug,
                                                        wallet: wallet.id,
                                                    })}
                                                    className="hover:underline"
                                                >
                                                    {wallet.name}
                                                </Link>
                                            </CardTitle>
                                            <p className="text-xs text-muted-foreground">
                                                {wallet.typeLabel}
                                                {wallet.accountNumber
                                                    ? ` · ${wallet.accountNumber}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                aria-label="Wallet actions"
                                            >
                                                <MoreVertical className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                onSelect={() =>
                                                    openEdit(wallet)
                                                }
                                            >
                                                <Pencil /> Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onSelect={() =>
                                                    setAddMoneyWallet(wallet)
                                                }
                                            >
                                                <PlusCircle /> Add money
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onSelect={() =>
                                                    toggleArchive(wallet)
                                                }
                                            >
                                                {wallet.archived ? (
                                                    <>
                                                        <ArchiveRestore />{' '}
                                                        Restore
                                                    </>
                                                ) : (
                                                    <>
                                                        <Archive /> Archive
                                                    </>
                                                )}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between">
                                        <Money
                                            amount={wallet.balance}
                                            currency={wallet.currency}
                                            colored
                                            className="text-2xl font-semibold"
                                        />
                                        {wallet.archived ? (
                                            <Badge variant="secondary">
                                                Archived
                                            </Badge>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}

                    {wallets.length === 0 ? (
                        <p className="col-span-full py-8 text-center text-muted-foreground">
                            No wallets yet.
                        </p>
                    ) : null}
                </div>
            </div>

            <WalletFormModal
                wallet={editing}
                walletTypes={walletTypes}
                open={formOpen}
                onOpenChange={setFormOpen}
                key={editing?.id ?? 'create'}
            />

            <TransactionFormSheet
                mode="income"
                wallets={wallets.map((w) => ({ id: w.id, name: w.name }))}
                categories={categories}
                open={addMoneyWallet !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setAddMoneyWallet(null);
                    }
                }}
                initialValues={
                    addMoneyWallet
                        ? { walletId: addMoneyWallet.id }
                        : undefined
                }
                key={addMoneyWallet?.id ?? 'add-money'}
            />
        </>
    );
}

WalletsIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Wallets',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
