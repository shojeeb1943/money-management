import { Download } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { download, edit } from '@/routes/export';

type Props = {
    hasCompany: boolean;
};

export default function Export({ hasCompany }: Props) {
    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Export"
                description="Download all of your current company's financial data as an organized Excel workbook."
            />

            {hasCompany ? (
                <>
                    <p className="text-sm text-muted-foreground">
                        The workbook includes separate sheets for Wallets,
                        Transactions, Categories, Budgets, Obligations,
                        Obligation Payments, and Recurring transactions.
                    </p>
                    <Button asChild>
                        <a href={download().url}>
                            <Download /> Download Excel
                        </a>
                    </Button>
                </>
            ) : (
                <p className="text-sm text-muted-foreground">
                    Select a company first to export its data.
                </p>
            )}
        </div>
    );
}

Export.layout = {
    breadcrumbs: [
        {
            title: 'Export settings',
            href: edit(),
        },
    ],
};
