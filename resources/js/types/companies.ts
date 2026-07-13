export type CompanyRole = 'owner' | 'admin' | 'member';

export type Company = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: CompanyRole;
    roleLabel?: string;
    timezone?: string;
    currency?: string;
    isCurrent?: boolean;
};

export type CompanyPermissions = {
    canUpdateCompany: boolean;
    canDeleteCompany: boolean;
};
