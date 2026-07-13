import { Head, usePage } from '@inertiajs/react';
import Money from '@/components/finance/money';
import ReportPeriodPicker from '@/components/finance/report-period-picker';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { cashFlow, index as reportsIndex } from '@/routes/reports';

type Props = {
    report: {
        operatingInflow: number;
        operatingOutflow: number;
        financingInflow: number;
        financingOutflow: number;
        netOperating: number;
        netFinancing: number;
        netChange: number;
        openingBalance: number;
        closingBalance: number;
    };
    from: string;
    to: string;
};

export default function CashFlowPage({ report, from, to }: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    return (
        <>
            <Head title="Cash Flow" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Cash Flow"
                        description={`${from} to ${to}`}
                    />
                    <ReportPeriodPicker
                        from={from}
                        to={to}
                        url={cashFlow.url({
                            current_company: currentCompany.slug,
                        })}
                    />
                </div>

                <Card className="max-w-2xl">
                    <CardContent className="space-y-6 pt-6">
                        <div className="flex items-center justify-between py-1">
                            <span className="font-medium">
                                Opening cash balance
                            </span>
                            <Money amount={report.openingBalance} />
                        </div>

                        <div className="space-y-1">
                            <h3 className="font-semibold">
                                Operating activities
                            </h3>
                            <div className="flex items-center justify-between py-1">
                                <span>Cash received (income)</span>
                                <Money amount={report.operatingInflow} />
                            </div>
                            <div className="flex items-center justify-between py-1">
                                <span>Cash spent (expenses)</span>
                                <Money amount={-report.operatingOutflow} />
                            </div>
                            <Separator />
                            <div className="flex items-center justify-between py-1 font-semibold">
                                <span>Net operating cash</span>
                                <Money amount={report.netOperating} colored />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <h3 className="font-semibold">
                                Financing activities
                            </h3>
                            <div className="flex items-center justify-between py-1">
                                <span>
                                    Capital investments &amp; opening balances
                                </span>
                                <Money amount={report.financingInflow} />
                            </div>
                            <div className="flex items-center justify-between py-1">
                                <span>Capital withdrawals</span>
                                <Money amount={-report.financingOutflow} />
                            </div>
                            <Separator />
                            <div className="flex items-center justify-between py-1 font-semibold">
                                <span>Net financing cash</span>
                                <Money amount={report.netFinancing} colored />
                            </div>
                        </div>

                        <div className="space-y-2 rounded-lg bg-muted p-3">
                            <div className="flex items-center justify-between font-semibold">
                                <span>Net change in cash</span>
                                <Money amount={report.netChange} colored />
                            </div>
                            <div className="flex items-center justify-between text-lg font-bold">
                                <span>Closing cash balance</span>
                                <Money amount={report.closingBalance} />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CashFlowPage.layout = (props: {
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
              { title: 'Cash Flow', href: '' },
          ]
        : [],
});
