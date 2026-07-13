import {
    CartesianGrid,
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import ChartTooltip from '@/components/finance/charts/chart-tooltip';
import { useCurrency } from '@/hooks/use-currency';

type Point = {
    month: string;
    profit: number;
};

export default function ProfitTrendChart({ data }: { data: Point[] }) {
    const { symbol } = useCurrency();

    return (
        <ResponsiveContainer width="100%" height={280}>
            <LineChart
                data={data}
                margin={{ top: 8, right: 8, left: 8, bottom: 0 }}
            >
                <CartesianGrid
                    vertical={false}
                    stroke="var(--border)"
                    strokeDasharray="3 3"
                />
                <XAxis
                    dataKey="month"
                    tickLine={false}
                    axisLine={false}
                    tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                />
                <YAxis
                    tickLine={false}
                    axisLine={false}
                    tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                    tickFormatter={(value: number) =>
                        `${symbol}${(value / 100000).toFixed(0)}k`
                    }
                />
                <ReferenceLine y={0} stroke="var(--border)" />
                <Tooltip
                    content={<ChartTooltip />}
                    cursor={{
                        stroke: 'var(--muted-foreground)',
                        strokeDasharray: '3 3',
                    }}
                />
                <Line
                    type="monotone"
                    dataKey="profit"
                    name="Profit"
                    stroke="var(--chart-3)"
                    strokeWidth={2}
                    dot={{
                        r: 4,
                        fill: 'var(--chart-3)',
                        strokeWidth: 2,
                        stroke: 'var(--background)',
                    }}
                    activeDot={{ r: 5 }}
                />
            </LineChart>
        </ResponsiveContainer>
    );
}
