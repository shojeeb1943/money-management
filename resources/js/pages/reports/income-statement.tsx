import { Head, Link, usePage } from '@inertiajs/react';
import Money from '@/components/finance/money';
import ReportPeriodPicker from '@/components/finance/report-period-picker';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { incomeStatement, index as reportsIndex } from '@/routes/reports';
import { index as transactionsIndex } from '@/routes/transactions';

type Row = {
    id: number;
    name: string;
    color: string | null;
    amount: number;
    children: Array<{
        id: number;
        name: string;
        color: string | null;
        amount: number;
    }>;
};

type Props = {
    report: {
        income: Row[];
        expense: Row[];
        totalIncome: number;
        totalExpense: number;
        netProfit: number;
    };
    from: string;
    to: string;
};

function Section({
    title,
    rows,
    total,
    linkFor,
}: {
    title: string;
    rows: Row[];
    total: number;
    linkFor: (categoryId: number) => string;
}) {
    return (
        <div className="space-y-1">
            <h3 className="font-semibold">{title}</h3>
            {rows.map((row) => (
                <div key={row.id}>
                    <Link
                        href={linkFor(row.id)}
                        className="flex items-center justify-between rounded py-1 hover:bg-muted/50"
                    >
                        <span className="flex items-center gap-2">
                            <span
                                className="size-2.5 rounded-full"
                                style={{
                                    backgroundColor: row.color ?? '#475569',
                                }}
                            />
                            {row.name}
                        </span>
                        <Money amount={row.amount} />
                    </Link>
                    {row.children.map((child) => (
                        <Link
                            key={child.id}
                            href={linkFor(child.id)}
                            className="flex items-center justify-between rounded py-1 pl-6 text-sm text-muted-foreground hover:bg-muted/50"
                        >
                            <span>{child.name}</span>
                            <Money amount={child.amount} />
                        </Link>
                    ))}
                </div>
            ))}
            {rows.length === 0 ? (
                <p className="py-2 text-sm text-muted-foreground">
                    Nothing recorded.
                </p>
            ) : null}
            <Separator />
            <div className="flex items-center justify-between py-1 font-semibold">
                <span>Total {title.toLowerCase()}</span>
                <Money amount={total} />
            </div>
        </div>
    );
}

export default function IncomeStatementPage({ report, from, to }: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    return (
        <>
            <Head title="Income Statement" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Income Statement"
                        description={`${from} to ${to}`}
                    />
                    <ReportPeriodPicker
                        from={from}
                        to={to}
                        url={incomeStatement.url({
                            current_company: currentCompany.slug,
                        })}
                    />
                </div>

                <Card className="max-w-2xl">
                    <CardContent className="space-y-6 pt-6">
                        <Section
                            title="Income"
                            rows={report.income}
                            total={report.totalIncome}
                            linkFor={(categoryId) =>
                                transactionsIndex.url(
                                    { current_company: currentCompany.slug },
                                    {
                                        query: {
                                            category: String(categoryId),
                                            from,
                                            to,
                                        },
                                    },
                                )
                            }
                        />
                        <Section
                            title="Expenses"
                            rows={report.expense}
                            total={report.totalExpense}
                            linkFor={(categoryId) =>
                                transactionsIndex.url(
                                    { current_company: currentCompany.slug },
                                    {
                                        query: {
                                            category: String(categoryId),
                                            from,
                                            to,
                                        },
                                    },
                                )
                            }
                        />

                        <div className="flex items-center justify-between rounded-lg bg-muted p-3 text-lg font-bold">
                            <span>Net profit</span>
                            <Money amount={report.netProfit} colored />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

IncomeStatementPage.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Reports',
                  href: reportsIndex({
                      current_company: props.currentCompany.slug,
                  }),
              },
              { title: 'Income Statement', href: '' },
          ]
        : [],
});
