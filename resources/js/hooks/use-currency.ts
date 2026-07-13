import { usePage } from '@inertiajs/react';
import { currencySymbol } from '@/lib/money';

export function useCurrency(): { currency: string; symbol: string } {
    const { currentCompany } = usePage().props;
    const currency = currentCompany?.currency ?? 'BDT';

    return { currency, symbol: currencySymbol(currency).trim() };
}
