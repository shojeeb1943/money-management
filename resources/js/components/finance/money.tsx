import { useCurrency } from '@/hooks/use-currency';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

type Props = {
    amount: number;
    currency?: string;
    colored?: boolean;
    className?: string;
};

export default function Money({
    amount,
    currency,
    colored = false,
    className,
}: Props) {
    const { currency: companyCurrency } = useCurrency();

    return (
        <span
            className={cn(
                'tabular-nums',
                colored &&
                    amount > 0 &&
                    'text-emerald-600 dark:text-emerald-400',
                colored && amount < 0 && 'text-red-600 dark:text-red-400',
                className,
            )}
        >
            {formatMoney(amount, currency ?? companyCurrency)}
        </span>
    );
}
