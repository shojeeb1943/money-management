import { Form, Head, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import Money from '@/components/finance/money';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import { destroy, index, store } from '@/routes/budgets';

type BudgetRow = {
    id: number;
    categoryId: number;
    categoryName: string;
    categoryColor: string | null;
    amount: number;
    alertThreshold: number;
    period: string;
    spent: number;
};

type Option = { id: number; name: string };

type Props = {
    budgets: BudgetRow[];
    categories: Option[];
};

export default function BudgetsIndex({ budgets, categories }: Props) {
    const { currentCompany } = usePage().props;
    const { symbol } = useCurrency();
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<BudgetRow | null>(null);
    const [categoryId, setCategoryId] = useState('');
    const [period, setPeriod] = useState('monthly');
    const [deleting, setDeleting] = useState<BudgetRow | null>(null);

    if (!currentCompany) {
        return null;
    }

    const openForm = (budget: BudgetRow | null) => {
        setEditing(budget);
        setCategoryId(budget ? String(budget.categoryId) : '');
        setPeriod(budget?.period ?? 'monthly');
        setFormOpen(true);
    };

    const removeBudget = (budget: BudgetRow) => {
        setDeleting(budget);
    };

    const confirmRemove = () => {
        if (!deleting) {
            return;
        }

        router.delete(
            destroy.url({
                current_company: currentCompany.slug,
                budget: deleting.id,
            }),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Budgets" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Budgets"
                        description="Monthly spending limits per expense category"
                    />
                    <Button onClick={() => openForm(null)}>
                        <Plus /> New budget
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {budgets.map((budget) => {
                        const percent =
                            budget.amount > 0
                                ? Math.min(
                                      150,
                                      Math.round(
                                          (budget.spent * 100) / budget.amount,
                                      ),
                                  )
                                : 0;
                        const barColor =
                            percent >= 100
                                ? 'bg-red-500'
                                : percent >= budget.alertThreshold
                                  ? 'bg-amber-500'
                                  : 'bg-emerald-500';

                        return (
                            <Card key={budget.id}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <span
                                            className="size-2.5 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    budget.categoryColor ??
                                                    '#475569',
                                            }}
                                        />
                                        {budget.categoryName}
                                    </CardTitle>
                                    <div className="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => openForm(budget)}
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Delete budget"
                                            onClick={() => removeBudget(budget)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    <div className="flex items-baseline justify-between">
                                        <Money
                                            amount={budget.spent}
                                            className="text-xl font-semibold"
                                        />
                                        <span className="text-sm text-muted-foreground">
                                            of <Money amount={budget.amount} />{' '}
                                            ({percent}%)
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
                                    <p className="text-xs text-muted-foreground">
                                        {budget.period.charAt(0).toUpperCase() +
                                            budget.period.slice(1)}{' '}
                                        · alerts at {budget.alertThreshold}%
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}

                    {budgets.length === 0 ? (
                        <p className="col-span-full py-8 text-center text-muted-foreground">
                            No budgets yet. Set one to get spending alerts.
                        </p>
                    ) : null}
                </div>
            </div>

            <ConfirmDialog
                title={`Remove ${deleting?.categoryName} budget?`}
                description="Spending alerts for this category stop. Transactions are not affected."
                confirmLabel="Remove budget"
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                onConfirm={confirmRemove}
            />

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent>
                    <Form
                        key={String(formOpen)}
                        {...store.form({
                            current_company: currentCompany.slug,
                        })}
                        className="space-y-6"
                        onSuccess={() => setFormOpen(false)}
                    >
                        {({ errors, processing }) => (
                            <>
                                <DialogHeader>
                                    <DialogTitle>
                                        {editing ? 'Edit budget' : 'New budget'}
                                    </DialogTitle>
                                    <DialogDescription>
                                        You'll get a toast alert when spending
                                        crosses the threshold.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-2">
                                    <Label>Expense category</Label>
                                    <Select
                                        value={categoryId}
                                        onValueChange={setCategoryId}
                                        disabled={Boolean(editing)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={String(category.id)}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value={categoryId}
                                    />
                                    <InputError message={errors.category_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Period</Label>
                                    <Select
                                        value={period}
                                        onValueChange={setPeriod}
                                        disabled={Boolean(editing)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="monthly">
                                                Monthly
                                            </SelectItem>
                                            <SelectItem value="quarterly">
                                                Quarterly
                                            </SelectItem>
                                            <SelectItem value="yearly">
                                                Yearly
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="period"
                                        value={period}
                                    />
                                    <InputError message={errors.period} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="budget-amount">
                                        Monthly limit ({symbol})
                                    </Label>
                                    <Input
                                        id="budget-amount"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        defaultValue={
                                            editing
                                                ? String(editing.amount / 100)
                                                : ''
                                        }
                                        required
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="budget-threshold">
                                        Alert threshold (%)
                                    </Label>
                                    <Input
                                        id="budget-threshold"
                                        name="alert_threshold"
                                        type="number"
                                        min="1"
                                        max="100"
                                        defaultValue={
                                            editing?.alertThreshold ?? 80
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.alert_threshold}
                                    />
                                </div>

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        Save budget
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}

BudgetsIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Budgets',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
