import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowUpRight,
    HandCoins,
    HandHeart,
    Landmark,
    TrendingUp,
    Waves,
} from 'lucide-react';
import ExpenseDonutChart from '@/components/finance/charts/expense-donut-chart';
import IncomeExpenseChart from '@/components/finance/charts/income-expense-chart';
import ProfitTrendChart from '@/components/finance/charts/profit-trend-chart';
import Money from '@/components/finance/money';
import ReportPeriodPicker from '@/components/finance/report-period-picker';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/date';
import { dashboard } from '@/routes';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as obligationsIndex } from '@/routes/obligations';
import { index as transactionsIndex } from '@/routes/transactions';

const OBLIGATION_ICONS: Record<string, typeof Landmark> = {
    loan: Landmark,
    lend: HandCoins,
    safekeeping: HandHeart,
};

type TrendPoint = {
    month: string;
    income: number;
    expense: number;
    profit: number;
};
type TopCategory = {
    id: number;
    name: string;
    color: string | null;
    amount: number;
};
type RecentTransaction = {
    id: number;
    type: string;
    typeLabel: string;
    description: string | null;
    categoryName: string | null;
    walletName: string;
    amount: number;
    signedAmount: number;
    date: string;
};

type BudgetProgress = {
    id: number;
    categoryName: string;
    categoryColor: string | null;
    amount: number;
    period: string;
    spent: number;
};

type ObligationSummary = {
    kind: string;
    label: string;
    remaining: number;
    count: number;
};

type Props = {
    totalCash?: number;
    from?: string;
    to?: string;
    periodIncome?: number;
    periodExpense?: number;
    periodProfit?: number;
    periodCashFlow?: number;
    trend?: TrendPoint[];
    budgets?: BudgetProgress[];
    obligationSummary?: ObligationSummary[];
    topExpenseCategories?: TopCategory[];
    recentTransactions?: RecentTransaction[];
};

type StatCard = {
    title: string;
    amount: number;
    colored?: boolean;
    icon: typeof TrendingUp;
    iconClass?: string;
};

export default function Dashboard({
    totalCash = 0,
    from = '',
    to = '',
    periodIncome = 0,
    periodExpense = 0,
    periodProfit = 0,
    periodCashFlow = 0,
    trend = [],
    budgets = [],
    obligationSummary = [],
    topExpenseCategories = [],
    recentTransactions = [],
}: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    const slug = currentCompany.slug;

    const stats: StatCard[] = [
        { title: 'Total cash', amount: totalCash, icon: Landmark },
        {
            title: 'Income',
            amount: periodIncome,
            icon: ArrowUpRight,
            iconClass: 'text-emerald-500',
        },
        {
            title: 'Expense',
            amount: periodExpense,
            icon: ArrowDownRight,
            iconClass: 'text-red-500',
        },
        {
            title: 'Profit',
            amount: periodProfit,
            colored: true,
            icon: TrendingUp,
        },
        {
            title: 'Cash flow',
            amount: periodCashFlow,
            colored: true,
            icon: Waves,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex justify-end">
                    <ReportPeriodPicker
                        from={from}
                        to={to}
                        url={dashboard.url(slug)}
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {stats.map((stat) => {
                        const Icon = stat.icon;

                        return (
                            <Card key={stat.title}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-1 text-sm font-normal text-muted-foreground">
                                        <Icon
                                            className={`size-4 ${stat.iconClass ?? ''}`}
                                        />
                                        {stat.title}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Money
                                        amount={stat.amount}
                                        colored={stat.colored}
                                        className="text-2xl font-semibold"
                                    />
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Income vs expense</CardTitle>
                            <CardDescription>Last 6 months</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <IncomeExpenseChart data={trend} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Profit trend</CardTitle>
                            <CardDescription>Last 6 months</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ProfitTrendChart data={trend} />
                        </CardContent>
                    </Card>
                </div>

                {budgets.length > 0 ? (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle>Budgets</CardTitle>
                            <Link
                                href={budgetsIndex({ current_company: slug })}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                View all
                            </Link>
                        </CardHeader>
                        <CardContent className="grid gap-6 sm:grid-cols-2">
                            {budgets.map((budget) => {
                                const percent =
                                    budget.amount > 0
                                        ? Math.round(
                                              (budget.spent * 100) /
                                                  budget.amount,
                                          )
                                        : 0;
                                const barColor =
                                    percent >= 100
                                        ? 'bg-red-500'
                                        : percent >= 80
                                          ? 'bg-amber-500'
                                          : 'bg-emerald-500';

                                return (
                                    <div key={budget.id} className="space-y-2">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-2">
                                                <span
                                                    className="size-2.5 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            budget.categoryColor ??
                                                            'var(--muted-foreground)',
                                                    }}
                                                />
                                                {budget.categoryName}
                                            </span>
                                            <span className="text-muted-foreground">
                                                <Money amount={budget.spent} />{' '}
                                                of{' '}
                                                <Money amount={budget.amount} />
                                            </span>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className={`h-full rounded-full ${barColor}`}
                                                style={{
                                                    width: `${Math.min(100, percent)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                ) : null}

                {obligationSummary.some((item) => item.count > 0) ? (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle>Obligations</CardTitle>
                            <Link
                                href={obligationsIndex({
                                    current_company: slug,
                                })}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                View all
                            </Link>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-3">
                            {obligationSummary.map((item) => {
                                const Icon =
                                    OBLIGATION_ICONS[item.kind] ?? Landmark;

                                return (
                                    <div
                                        key={item.kind}
                                        className="flex items-center gap-3 rounded-lg border p-3"
                                    >
                                        <Icon className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                {item.label} ·{' '}
                                                {item.count}
                                            </p>
                                            <Money
                                                amount={item.remaining}
                                                className="text-lg font-semibold"
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                ) : null}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Top expense categories</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ExpenseDonutChart data={topExpenseCategories} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle>Recent transactions</CardTitle>
                            <Link
                                href={transactionsIndex({
                                    current_company: slug,
                                })}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                View all
                            </Link>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {recentTransactions.map((transaction) => (
                                <div
                                    key={transaction.id}
                                    className="flex items-center justify-between text-sm"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {transaction.description ??
                                                transaction.categoryName ??
                                                transaction.typeLabel}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {transaction.walletName} ·{' '}
                                            {formatDate(transaction.date)}
                                        </p>
                                    </div>
                                    {transaction.type === 'transfer' ? (
                                        <Money amount={transaction.amount} />
                                    ) : (
                                        <Money
                                            amount={transaction.signedAmount}
                                            colored
                                        />
                                    )}
                                </div>
                            ))}
                            {recentTransactions.length === 0 ? (
                                <p className="py-6 text-center text-muted-foreground">
                                    No transactions yet.
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentCompany?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentCompany
                ? dashboard(props.currentCompany.slug)
                : '/',
        },
    ],
});
