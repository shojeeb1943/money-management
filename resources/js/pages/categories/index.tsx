import { Head, router, usePage } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    MoreVertical,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import CategoryFormModal from '@/components/finance/category-form-modal';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { archive, destroy, index } from '@/routes/categories';
import type { Category } from '@/types';

type Props = {
    categories: Category[];
};

type ModalState = {
    open: boolean;
    kind: 'income' | 'expense';
    category: Category | null;
    parent: Category | null;
};

export default function CategoriesIndex({ categories }: Props) {
    const { currentCompany } = usePage().props;
    const [modal, setModal] = useState<ModalState>({
        open: false,
        kind: 'income',
        category: null,
        parent: null,
    });
    const [deleting, setDeleting] = useState<Category | null>(null);

    if (!currentCompany) {
        return null;
    }

    const toggleArchive = (category: Category) => {
        router.patch(
            archive.url({
                current_company: currentCompany.slug,
                category: category.id,
            }),
            {},
            { preserveScroll: true },
        );
    };

    const deleteCategory = (category: Category) => {
        setDeleting(category);
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(
            destroy.url({
                current_company: currentCompany.slug,
                category: deleting.id,
            }),
            {
                preserveScroll: true,
            },
        );
    };

    const renderRow = (category: Category, isChild = false) => (
        <div
            key={category.id}
            className={`flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted/50 ${isChild ? 'ml-6' : ''}`}
        >
            <div className="flex items-center gap-2">
                <span
                    className="size-3 rounded-full"
                    style={{ backgroundColor: category.color ?? '#475569' }}
                />
                <span
                    className={
                        category.archived
                            ? 'text-muted-foreground line-through'
                            : ''
                    }
                >
                    {category.name}
                </span>
                {category.archived ? (
                    <Badge variant="secondary" className="text-xs">
                        Archived
                    </Badge>
                ) : null}
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="sm"
                        aria-label={`${category.name} actions`}
                    >
                        <MoreVertical className="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {!isChild ? (
                        <DropdownMenuItem
                            onSelect={() =>
                                setModal({
                                    open: true,
                                    kind: category.kind,
                                    category: null,
                                    parent: category,
                                })
                            }
                        >
                            <Plus /> Add sub-category
                        </DropdownMenuItem>
                    ) : null}
                    <DropdownMenuItem
                        onSelect={() =>
                            setModal({
                                open: true,
                                kind: category.kind,
                                category,
                                parent: null,
                            })
                        }
                    >
                        <Pencil /> Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => toggleArchive(category)}>
                        {category.archived ? (
                            <>
                                <ArchiveRestore /> Restore
                            </>
                        ) : (
                            <>
                                <Archive /> Archive
                            </>
                        )}
                    </DropdownMenuItem>
                    {!category.hasActivity && !category.hasChildren ? (
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => deleteCategory(category)}
                        >
                            <Trash2 /> Delete
                        </DropdownMenuItem>
                    ) : null}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );

    const renderKind = (kind: 'income' | 'expense', title: string) => {
        const parents = categories.filter(
            (category) => category.kind === kind && category.parentId === null,
        );

        return (
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <CardTitle>{title}</CardTitle>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setModal({
                                open: true,
                                kind,
                                category: null,
                                parent: null,
                            })
                        }
                    >
                        <Plus /> Add
                    </Button>
                </CardHeader>
                <CardContent className="space-y-1">
                    {parents.map((parent) => (
                        <div key={parent.id}>
                            {renderRow(parent)}
                            {categories
                                .filter(
                                    (category) =>
                                        category.parentId === parent.id,
                                )
                                .map((child) => renderRow(child, true))}
                        </div>
                    ))}
                    {parents.length === 0 ? (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            No {kind} categories.
                        </p>
                    ) : null}
                </CardContent>
            </Card>
        );
    };

    return (
        <>
            <Head title="Categories" />

            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    title="Categories"
                    description="Organise income and expenses for reports and budgets"
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    {renderKind('income', 'Income')}
                    {renderKind('expense', 'Expense')}
                </div>
            </div>

            <ConfirmDialog
                title={`Delete "${deleting?.name}"?`}
                description="This permanently removes the category. Only unused categories can be deleted."
                confirmLabel="Delete category"
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                onConfirm={confirmDelete}
            />

            <CategoryFormModal
                key={`${modal.category?.id ?? 'new'}-${modal.parent?.id ?? 'root'}-${modal.kind}`}
                category={modal.category}
                kind={modal.kind}
                parent={modal.parent}
                open={modal.open}
                onOpenChange={(open) =>
                    setModal((state) => ({ ...state, open }))
                }
            />
        </>
    );
}

CategoriesIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Categories',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
