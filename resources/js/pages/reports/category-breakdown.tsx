import { Head, router, usePage } from '@inertiajs/react';
import ExpenseDonutChart from '@/components/finance/charts/expense-donut-chart';
import Money from '@/components/finance/money';
import ReportPeriodPicker from '@/components/finance/report-period-picker';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { categoryBreakdown, index } from '@/routes/reports';

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
    rows: Row[];
    total: number;
    kind: 'income' | 'expense';
    from: string;
    to: string;
};

export default function CategoryBreakdownPage({
    rows,
    total,
    kind,
    from,
    to,
}: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    const url = categoryBreakdown.url({ current_company: currentCompany.slug });

    return (
        <>
            <Head title="Category Breakdown" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Category Breakdown"
                        description={`${from} to ${to}`}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={kind}
                            onValueChange={(value) =>
                                router.get(
                                    url,
                                    { kind: value, from, to },
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                    },
                                )
                            }
                        >
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="expense">Expense</SelectItem>
                                <SelectItem value="income">Income</SelectItem>
                            </SelectContent>
                        </Select>
                        <ReportPeriodPicker from={from} to={to} url={url} />
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardContent className="pt-6">
                            <ExpenseDonutChart data={rows} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Category</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Share
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <span
                                                        className="size-2.5 rounded-full"
                                                        style={{
                                                            backgroundColor:
                                                                row.color ??
                                                                '#475569',
                                                        }}
                                                    />
                                                    {row.name}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Money amount={row.amount} />
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground">
                                                {total > 0
                                                    ? `${Math.round((row.amount * 100) / total)}%`
                                                    : '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}

                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                Nothing recorded in this period.
                                            </TableCell>
                                        </TableRow>
                                    ) : null}
                                </TableBody>
                            </Table>

                            {rows.length > 0 ? (
                                <p className="mt-4 flex justify-between border-t pt-3 font-semibold">
                                    <span>Total</span>
                                    <Money amount={total} />
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CategoryBreakdownPage.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Reports',
                  href: index({ current_company: props.currentCompany.slug }),
              },
              { title: 'Category Breakdown', href: '' },
          ]
        : [],
});
