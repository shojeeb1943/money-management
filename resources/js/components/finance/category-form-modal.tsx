import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import type { Category } from '@/types';
import { store, update } from '@/routes/categories';

const COLORS = [
    '#16a34a',
    '#0d9488',
    '#2563eb',
    '#7c3aed',
    '#db2777',
    '#dc2626',
    '#ea580c',
    '#475569',
];

type Props = {
    category?: Category | null;
    kind: 'income' | 'expense';
    parent?: Category | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function CategoryFormModal({
    category,
    kind,
    parent,
    open,
    onOpenChange,
}: Props) {
    const { currentCompany } = usePage().props;
    const [color, setColor] = useState(category?.color ?? COLORS[0]);

    if (!currentCompany) {
        return null;
    }

    const formProps = category
        ? update.form({
              current_company: currentCompany.slug,
              category: category.id,
          })
        : store.form({ current_company: currentCompany.slug });

    const title = category
        ? 'Edit category'
        : parent
          ? `New sub-category under ${parent.name}`
          : `New ${kind} category`;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...formProps}
                    className="space-y-6"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>{title}</DialogTitle>
                                <DialogDescription>
                                    Categories organise your{' '}
                                    {kind === 'income' ? 'income' : 'spending'}{' '}
                                    for reports and budgets.
                                </DialogDescription>
                            </DialogHeader>

                            <input
                                type="hidden"
                                name="kind"
                                value={category?.kind ?? kind}
                            />
                            {parent && !category ? (
                                <input
                                    type="hidden"
                                    name="parent_id"
                                    value={parent.id}
                                />
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="category-name">Name</Label>
                                <Input
                                    id="category-name"
                                    name="name"
                                    defaultValue={category?.name ?? ''}
                                    placeholder="e.g. Marketing"
                                    required
                                />
                                <InputError message={errors.name} />
                                <InputError message={errors.kind} />
                                <InputError message={errors.parent_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Color</Label>
                                <div className="flex gap-2">
                                    {COLORS.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            aria-label={`Color ${option}`}
                                            onClick={() => setColor(option)}
                                            className="size-6 rounded-full border-2"
                                            style={{
                                                backgroundColor: option,
                                                borderColor:
                                                    color === option
                                                        ? 'var(--foreground)'
                                                        : 'transparent',
                                            }}
                                        />
                                    ))}
                                </div>
                                <input
                                    type="hidden"
                                    name="color"
                                    value={color ?? ''}
                                />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {category
                                        ? 'Save changes'
                                        : 'Create category'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
