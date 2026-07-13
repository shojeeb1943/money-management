export interface Wallet {
    id: number;
    name: string;
    type: string;
    typeLabel: string;
    accountNumber: string | null;
    icon: string | null;
    color: string | null;
    currency: string;
    openingBalance: number;
    balance: number;
    archived: boolean;
}

export interface WalletTypeOption {
    value: string;
    label: string;
}

export interface Category {
    id: number;
    parentId: number | null;
    kind: 'income' | 'expense';
    name: string;
    icon: string | null;
    color: string | null;
    archived: boolean;
    hasActivity: boolean;
    hasChildren: boolean;
}

export interface TransactionRow {
    id: number;
    type:
        | 'income'
        | 'expense'
        | 'transfer'
        | 'capital_withdrawal'
        | 'capital_investment';
    typeLabel: string;
    walletId: number;
    walletName: string;
    counterWalletId: number | null;
    counterWalletName: string | null;
    categoryId: number | null;
    categoryName: string | null;
    categoryColor: string | null;
    amount: number;
    signedAmount: number;
    currency: string;
    date: string;
    description: string | null;
    reference: string | null;
    voided: boolean;
}

export interface TransactionFilters {
    type?: string;
    wallet?: string;
    category?: string;
    from?: string;
    to?: string;
    search?: string;
    status?: string;
}

export interface LedgerRow {
    id: number;
    date: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
}

export interface SimplePagination {
    currentPage: number;
    lastPage: number;
    total: number;
}
