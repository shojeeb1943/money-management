import { router, usePage } from '@inertiajs/react';
import { ArrowLeftRight, Search, Shapes, WalletCards } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Money from '@/components/finance/money';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { index as categoriesIndex } from '@/routes/categories';
import { index as searchIndex } from '@/routes/search';
import { index as transactionsIndex } from '@/routes/transactions';
import { show as walletShow } from '@/routes/wallets';

type TransactionHit = {
    id: number;
    description: string;
    walletName: string;
    signedAmount: number;
    amount: number;
    type: string;
    date: string;
    voided: boolean;
};

type WalletHit = {
    id: number;
    name: string;
    typeLabel: string;
    balance: number;
    currency: string;
};

type CategoryHit = { id: number; name: string; kindLabel: string };

type Results = {
    transactions: TransactionHit[];
    wallets: WalletHit[];
    categories: CategoryHit[];
};

type FlatItem = {
    key: string;
    url: string;
    render: () => React.ReactNode;
};

const EMPTY: Results = { transactions: [], wallets: [], categories: [] };

export default function GlobalSearch() {
    const { currentCompany } = usePage().props;
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Results>(EMPTY);
    const [loading, setLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const abortRef = useRef<AbortController | null>(null);

    const slug = currentCompany?.slug;
    const searchTerm = query.trim();
    const searchActive = Boolean(slug) && searchTerm.length >= 2;

    const changeOpen = useCallback((next: boolean) => {
        setOpen(next);

        if (!next) {
            setQuery('');
            setResults(EMPTY);
            setActiveIndex(0);
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                changeOpen(!open);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, changeOpen]);

    useEffect(() => {
        if (!slug || !searchActive) {
            return;
        }

        const timer = setTimeout(() => {
            setLoading(true);
            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;

            fetch(
                searchIndex.url(
                    { current_company: slug },
                    { query: { q: query.trim() } },
                ),
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            )
                .then((response) => response.json())
                .then((data: Results) => {
                    setResults(data);
                    setActiveIndex(0);
                    setLoading(false);
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        setLoading(false);
                    }
                });
        }, 250);

        return () => clearTimeout(timer);
    }, [query, slug, searchActive]);

    const visibleResults = searchActive ? results : EMPTY;

    const items: FlatItem[] = useMemo(() => {
        if (!slug) {
            return [];
        }

        return [
            ...visibleResults.transactions.map((hit) => ({
                key: `transaction-${hit.id}`,
                url: transactionsIndex.url(
                    { current_company: slug },
                    { query: { search: query.trim() } },
                ),
                render: () => (
                    <>
                        <ArrowLeftRight className="size-4 shrink-0 text-muted-foreground" />
                        <span className="flex-1 truncate">
                            {hit.description}
                            <span className="ml-2 text-xs text-muted-foreground">
                                {hit.walletName} · {hit.date}
                                {hit.voided ? ' · voided' : ''}
                            </span>
                        </span>
                        {hit.type === 'transfer' ? (
                            <Money amount={hit.amount} className="text-sm" />
                        ) : (
                            <Money
                                amount={hit.signedAmount}
                                colored
                                className="text-sm"
                            />
                        )}
                    </>
                ),
            })),
            ...visibleResults.wallets.map((hit) => ({
                key: `wallet-${hit.id}`,
                url: walletShow.url({ current_company: slug, wallet: hit.id }),
                render: () => (
                    <>
                        <WalletCards className="size-4 shrink-0 text-muted-foreground" />
                        <span className="flex-1 truncate">
                            {hit.name}
                            <span className="ml-2 text-xs text-muted-foreground">
                                {hit.typeLabel}
                            </span>
                        </span>
                        <Money
                            amount={hit.balance}
                            currency={hit.currency}
                            className="text-sm"
                        />
                    </>
                ),
            })),
            ...visibleResults.categories.map((hit) => ({
                key: `category-${hit.id}`,
                url: categoriesIndex.url({ current_company: slug }),
                render: () => (
                    <>
                        <Shapes className="size-4 shrink-0 text-muted-foreground" />
                        <span className="flex-1 truncate">{hit.name}</span>
                        <span className="text-xs text-muted-foreground">
                            {hit.kindLabel}
                        </span>
                    </>
                ),
            })),
        ];
    }, [visibleResults, slug, query]);

    const groupLabels: Record<string, string> = {
        transaction: 'Transactions',
        wallet: 'Wallets',
        category: 'Categories',
    };

    const visit = useCallback(
        (item: FlatItem | undefined) => {
            if (!item) {
                return;
            }

            changeOpen(false);
            router.visit(item.url);
        },
        [changeOpen],
    );

    const onInputKeyDown = (event: React.KeyboardEvent) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((current) =>
                Math.min(current + 1, items.length - 1),
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((current) => Math.max(current - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            visit(items[activeIndex]);
        }
    };

    if (!currentCompany) {
        return null;
    }

    const isMac =
        typeof navigator !== 'undefined' &&
        navigator.platform.toUpperCase().includes('MAC');

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                onClick={() => changeOpen(true)}
                className="w-56 justify-start gap-2 text-muted-foreground"
                data-test="global-search-trigger"
            >
                <Search className="size-4" />
                <span className="flex-1 text-left">Search…</span>
                <kbd className="pointer-events-none rounded border bg-muted px-1.5 py-0.5 font-sans text-[10px] font-medium">
                    {isMac ? '⌘' : 'Ctrl'} K
                </kbd>
            </Button>

            <Dialog open={open} onOpenChange={changeOpen}>
                <DialogContent className="top-[20%] translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-xl">
                    <DialogTitle className="sr-only">Global search</DialogTitle>

                    <div className="flex items-center gap-2 border-b px-4">
                        {loading && searchActive ? (
                            <Spinner className="size-4 shrink-0 text-muted-foreground" />
                        ) : (
                            <Search className="size-4 shrink-0 text-muted-foreground" />
                        )}
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            onKeyDown={onInputKeyDown}
                            placeholder="Search transactions, wallets, categories…"
                            className="h-12 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                            autoFocus
                        />
                    </div>

                    <div className="max-h-80 overflow-y-auto p-2">
                        {items.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                {query.trim().length < 2
                                    ? 'Type at least 2 characters to search.'
                                    : loading
                                      ? 'Searching…'
                                      : `No results for "${query}".`}
                            </p>
                        ) : (
                            items.map((item, index) => {
                                const group = item.key.split('-')[0];
                                const previousGroup =
                                    index > 0
                                        ? items[index - 1].key.split('-')[0]
                                        : null;

                                return (
                                    <div key={item.key}>
                                        {group !== previousGroup ? (
                                            <p className="px-2 pt-2 pb-1 text-xs font-medium text-muted-foreground">
                                                {groupLabels[group]}
                                            </p>
                                        ) : null}
                                        <button
                                            type="button"
                                            onClick={() => visit(item)}
                                            onMouseEnter={() =>
                                                setActiveIndex(index)
                                            }
                                            className={cn(
                                                'flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm',
                                                index === activeIndex
                                                    ? 'bg-muted'
                                                    : undefined,
                                            )}
                                        >
                                            {item.render()}
                                        </button>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
