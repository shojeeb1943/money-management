import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import CreateCompanyModal from '@/components/create-company-modal';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { edit, index } from '@/routes/companies';
import type { Company } from '@/types';

type Props = {
    companies: Company[];
};

export default function CompaniesIndex({ companies }: Props) {
    return (
        <>
            <Head title="Companies" />

            <h1 className="sr-only">Companies</h1>

            <div className="flex flex-col space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Companies"
                        description="Each company keeps its own wallets, transactions and reports"
                    />

                    <CreateCompanyModal>
                        <Button data-test="companies-new-company-button">
                            <Plus /> New company
                        </Button>
                    </CreateCompanyModal>
                </div>

                <div className="space-y-3">
                    {companies.map((company) => (
                        <div
                            key={company.id}
                            data-test="company-row"
                            className="flex items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-2">
                                <span className="font-medium">
                                    {company.name}
                                </span>
                                {company.isPersonal ? (
                                    <Badge variant="secondary">Personal</Badge>
                                ) : null}
                            </div>

                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            data-test="company-edit-button"
                                            asChild
                                        >
                                            <Link href={edit(company.slug)}>
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Edit company</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    ))}

                    {companies.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                            No companies yet.
                        </p>
                    ) : null}
                </div>
            </div>
        </>
    );
}

CompaniesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Companies',
            href: index(),
        },
    ],
};
