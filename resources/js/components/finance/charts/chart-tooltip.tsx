import { formatMoney } from '@/lib/money';

type TooltipEntry = {
    name?: string;
    value?: number | string;
    color?: string;
};

type Props = {
    active?: boolean;
    label?: string | number;
    payload?: TooltipEntry[];
};

export default function ChartTooltip({ active, label, payload }: Props) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    return (
        <div className="rounded-lg border bg-popover px-3 py-2 text-sm shadow-md">
            {label !== undefined ? (
                <p className="mb-1 font-medium text-popover-foreground">
                    {label}
                </p>
            ) : null}
            {payload.map((entry, index) => (
                <p
                    key={index}
                    className="flex items-center gap-2 text-muted-foreground"
                >
                    <span
                        className="size-2.5 rounded-full"
                        style={{ backgroundColor: entry.color }}
                    />
                    {entry.name}:{' '}
                    <span className="font-medium text-popover-foreground tabular-nums">
                        {formatMoney(Number(entry.value ?? 0))}
                    </span>
                </p>
            ))}
        </div>
    );
}
