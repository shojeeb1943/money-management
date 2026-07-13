import { Head, usePage } from '@inertiajs/react';
import IncomeExpenseChart from '@/components/finance/charts/income-expense-chart';
import Money from '@/components/finance/money';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/reports';

type MonthRow = {
    month: string;
    income: number;
    expense: number;
    profit: number;
};

type Props = {
    months: MonthRow[];
    totals: { income: number; expense: number; profit: number };
};

export default function MonthlySummaryPage({ months, totals }: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    return (
        <>
            <Head title="Monthly Summary" />

            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    title="Monthly Summary"
                    description="Last 12 months of income, expense and profit"
                />

                <Card>
                    <CardContent className="pt-6">
                        <IncomeExpenseChart data={months} />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Month</TableHead>
                                    <TableHead className="text-right">
                                        Income
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Expense
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Profit
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {months.map((row) => (
                                    <TableRow key={row.month}>
                                        <TableCell>{row.month}</TableCell>
                                        <TableCell className="text-right">
                                            <Money amount={row.income} />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Money amount={row.expense} />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Money
                                                amount={row.profit}
                                                colored
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell>Total</TableCell>
                                    <TableCell className="text-right">
                                        <Money amount={totals.income} />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Money amount={totals.expense} />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Money amount={totals.profit} colored />
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

MonthlySummaryPage.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Reports',
                  href: index({ current_company: props.currentCompany.slug }),
              },
              { title: 'Monthly Summary', href: '' },
          ]
        : [],
});
