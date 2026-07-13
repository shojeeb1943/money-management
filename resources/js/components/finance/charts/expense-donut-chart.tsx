import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import ChartTooltip from '@/components/finance/charts/chart-tooltip';
import Money from '@/components/finance/money';

type Slice = {
    id: number;
    name: string;
    color: string | null;
    amount: number;
};

const FALLBACK_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export default function ExpenseDonutChart({ data }: { data: Slice[] }) {
    const slices = data.filter((slice) => slice.amount > 0);

    if (slices.length === 0) {
        return (
            <p className="py-10 text-center text-sm text-muted-foreground">
                No expenses this month.
            </p>
        );
    }

    return (
        <div className="flex items-center gap-4">
            <ResponsiveContainer width="50%" height={220}>
                <PieChart>
                    <Tooltip content={<ChartTooltip />} />
                    <Pie
                        data={slices}
                        dataKey="amount"
                        nameKey="name"
                        innerRadius={55}
                        outerRadius={90}
                        paddingAngle={2}
                        stroke="var(--background)"
                        strokeWidth={2}
                    >
                        {slices.map((slice, index) => (
                            <Cell
                                key={slice.id}
                                fill={
                                    slice.color ??
                                    FALLBACK_COLORS[
                                        index % FALLBACK_COLORS.length
                                    ]
                                }
                            />
                        ))}
                    </Pie>
                </PieChart>
            </ResponsiveContainer>

            <ul className="flex-1 space-y-2">
                {slices.map((slice, index) => (
                    <li
                        key={slice.id}
                        className="flex items-center justify-between gap-2 text-sm"
                    >
                        <span className="flex items-center gap-2">
                            <span
                                className="size-2.5 rounded-full"
                                style={{
                                    backgroundColor: slice.color ?? undefined,
                                }}
                            >
                                {slice.color ? null : (
                                    <span
                                        className="block size-full rounded-full"
                                        style={{
                                            backgroundColor:
                                                FALLBACK_COLORS[
                                                    index %
                                                        FALLBACK_COLORS.length
                                                ],
                                        }}
                                    />
                                )}
                            </span>
                            <span className="text-muted-foreground">
                                {slice.name}
                            </span>
                        </span>
                        <Money amount={slice.amount} className="font-medium" />
                    </li>
                ))}
            </ul>
        </div>
    );
}
