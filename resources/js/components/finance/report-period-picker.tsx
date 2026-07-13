import { router, usePage } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { todayIn } from '@/lib/date';

type Props = {
    from: string;
    to: string;
    url: string;
};

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function lastDayOf(year: number, month: number): string {
    return `${year}-${pad(month)}-${pad(new Date(year, month, 0).getDate())}`;
}

function presetRange(
    preset: string,
    today: string,
): { from: string; to: string } {
    const [year, month] = today.split('-').map(Number);

    switch (preset) {
        case 'this_month':
            return {
                from: `${year}-${pad(month)}-01`,
                to: lastDayOf(year, month),
            };
        case 'last_month': {
            const previousYear = month === 1 ? year - 1 : year;
            const previousMonth = month === 1 ? 12 : month - 1;

            return {
                from: `${previousYear}-${pad(previousMonth)}-01`,
                to: lastDayOf(previousYear, previousMonth),
            };
        }
        case 'this_year':
            return { from: `${year}-01-01`, to: `${year}-12-31` };
        default:
            return { from: '2000-01-01', to: today };
    }
}

export default function ReportPeriodPicker({ from, to, url }: Props) {
    const { currentCompany } = usePage().props;
    const today = todayIn(currentCompany?.timezone);

    const apply = (nextFrom: string, nextTo: string) => {
        router.get(
            url,
            { from: nextFrom, to: nextTo },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <Select
                onValueChange={(preset) => {
                    const range = presetRange(preset, today);
                    apply(range.from, range.to);
                }}
            >
                <SelectTrigger className="w-36">
                    <SelectValue placeholder="Preset" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="this_month">This month</SelectItem>
                    <SelectItem value="last_month">Last month</SelectItem>
                    <SelectItem value="this_year">This year</SelectItem>
                    <SelectItem value="all_time">All time</SelectItem>
                </SelectContent>
            </Select>

            <Input
                type="date"
                value={from}
                onChange={(event) => apply(event.target.value, to)}
                className="w-36"
                aria-label="From date"
            />
            <span className="text-muted-foreground">–</span>
            <Input
                type="date"
                value={to}
                onChange={(event) => apply(from, event.target.value)}
                className="w-36"
                aria-label="To date"
            />
        </div>
    );
}
