export const CURRENCY_SYMBOLS: Record<string, string> = {
    BDT: '৳',
    USD: '$',
    EUR: '€',
    GBP: '£',
    INR: '₹',
    AED: 'AED ',
    SGD: 'S$',
    MYR: 'RM ',
};

export function currencySymbol(currency = 'BDT'): string {
    return CURRENCY_SYMBOLS[currency] ?? `${currency} `;
}

export function formatMoney(minorUnits: number, currency = 'BDT'): string {
    const sign = minorUnits < 0 ? '-' : '';
    const amount = (Math.abs(minorUnits) / 100).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${sign}${currencySymbol(currency)}${amount}`;
}
