import { useHttp, usePage } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import TransactionFormSheet from '@/components/finance/transaction-form-sheet';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { parse } from '@/routes/chatbot';

type Option = { id: number; name: string };
type CategoryOption = Option & {
    kind: 'income' | 'expense';
    parentId: number | null;
};

type ParseResult = {
    type: 'income' | 'expense' | 'transfer';
    amount: number;
    date: string;
    description: string | null;
    walletId: number | null;
    counterWalletId: number | null;
    categoryId: number | null;
};

type Props = {
    wallets: Option[];
    categories: CategoryOption[];
};

export default function ChatbotQuickAdd({ wallets, categories }: Props) {
    const { currentCompany } = usePage().props;
    const [open, setOpen] = useState(false);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [result, setResult] = useState<ParseResult | null>(null);
    const [error, setError] = useState<string | null>(null);
    const { data, setData, post, processing } = useHttp<
        { text: string },
        ParseResult
    >({ text: '' });

    if (!currentCompany) {
        return null;
    }

    const slug = currentCompany.slug;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (!data.text.trim()) {
            return;
        }

        setError(null);

        post(parse.url({ current_company: slug }), {
            onSuccess: (response) => {
                setResult(response);
                setOpen(false);
                setSheetOpen(true);
                setData('text', '');
            },
            onError: () => {
                setError(
                    'Could not parse that. Check Settings → AI, or try rephrasing.',
                );
            },
            onHttpException: () => {
                setError('Too many requests — wait a moment and try again.');
            },
            onNetworkError: () => {
                setError(
                    'Network error — check your connection and try again.',
                );
            },
        }).catch(() => {
            // onError/onHttpException/onNetworkError above already surfaced a message.
        });
    };

    return (
        <>
            <Button
                variant="outline"
                onClick={() => setOpen(true)}
                data-test="chatbot-quick-add-trigger"
            >
                <Sparkles /> Quick add
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogTitle>Quick add with AI</DialogTitle>
                    <DialogDescription>
                        Describe a transaction in your own words — English,
                        Bangla, or Banglish.
                    </DialogDescription>

                    <form onSubmit={submit} className="space-y-4">
                        <textarea
                            value={data.text}
                            onChange={(event) =>
                                setData('text', event.target.value)
                            }
                            placeholder="e.g. spent 500 on lunch today, or ajke lunch e 500 taka খরচ হইছে"
                            className="min-h-24 w-full rounded-md border border-input bg-transparent p-3 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            autoFocus
                        />

                        {error ? (
                            <p className="text-sm text-destructive">{error}</p>
                        ) : null}

                        <DialogFooter>
                            <Button
                                type="submit"
                                disabled={processing || !data.text.trim()}
                            >
                                {processing ? (
                                    <Spinner className="size-4" />
                                ) : null}
                                Parse
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {result ? (
                <TransactionFormSheet
                    key={`${result.type}-${result.amount}-${result.date}-${result.description}-${result.walletId}-${result.counterWalletId}-${result.categoryId}`}
                    mode={result.type}
                    wallets={wallets}
                    categories={categories}
                    open={sheetOpen}
                    onOpenChange={setSheetOpen}
                    initialValues={{
                        walletId: result.walletId ?? undefined,
                        counterWalletId: result.counterWalletId ?? undefined,
                        categoryId: result.categoryId ?? undefined,
                        amount: result.amount,
                        date: result.date,
                        description: result.description ?? undefined,
                    }}
                />
            ) : null}
        </>
    );
}
