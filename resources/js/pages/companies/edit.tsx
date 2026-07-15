import { Form, Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DeleteCompanyModal from '@/components/delete-company-modal';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Company } from '@/types';
import { edit, index, update } from '@/routes/companies';
import { update as updatePreferences } from '@/routes/companies/preferences';

type Props = {
    company: Company;
    canDelete: boolean;
    timezones: string[];
    currencies: { code: string; symbol: string }[];
};

const SELECT_CLASSES =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:max-w-sm dark:bg-input/30';

export default function CompanyEdit({
    company,
    canDelete,
    timezones,
    currencies,
}: Props) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const pageTitle = useMemo(() => `Edit ${company.name}`, [company.name]);

    return (
        <>
            <Head title={pageTitle} />

            <h1 className="sr-only">{pageTitle}</h1>

            <div className="flex flex-col space-y-10">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Company settings"
                        description="Update your company name and settings"
                    />

                    <Form {...update.form(company.slug)} className="space-y-6">
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Company name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        data-test="company-name-input"
                                        defaultValue={company.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        type="submit"
                                        data-test="company-save-button"
                                        disabled={processing}
                                    >
                                        Save
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Preferences"
                        description="Timezone and currency used across reports, dashboards and entries"
                    />

                    <Form
                        {...updatePreferences.form(company.slug)}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Timezone</Label>
                                    <select
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={company.timezone}
                                        className={SELECT_CLASSES}
                                    >
                                        {timezones.map((timezone) => (
                                            <option
                                                key={timezone}
                                                value={timezone}
                                            >
                                                {timezone.replace(/_/g, ' ')}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.timezone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <select
                                        id="currency"
                                        name="currency"
                                        defaultValue={company.currency}
                                        className={SELECT_CLASSES}
                                    >
                                        {currencies.map((option) => (
                                            <option
                                                key={option.code}
                                                value={option.code}
                                            >
                                                {option.code} ({option.symbol})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.currency} />
                                    <p className="text-xs text-muted-foreground">
                                        Reports and dashboards only count
                                        wallets in this currency.
                                    </p>
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Save
                                </Button>
                            </>
                        )}
                    </Form>
                </div>

                {canDelete ? (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Delete company"
                            description="Permanently delete your company"
                        />
                        <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                            <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                <p className="font-medium">Warning</p>
                                <p className="text-sm">
                                    Please proceed with caution, this cannot be
                                    undone.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                data-test="delete-company-button"
                                onClick={() => setDeleteDialogOpen(true)}
                            >
                                Delete company
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            {canDelete ? (
                <DeleteCompanyModal
                    company={company}
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                />
            ) : null}
        </>
    );
}

CompanyEdit.layout = (props: { company: { name: string; slug: string } }) => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: index(),
        },
        {
            title: props.company.name,
            href: edit(props.company.slug),
        },
    ],
});
