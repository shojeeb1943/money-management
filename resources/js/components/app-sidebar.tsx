import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    ChartColumn,
    History,
    LayoutGrid,
    PiggyBank,
    RefreshCw,
    Shapes,
    WalletCards,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { CompanySwitcher } from '@/components/company-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as auditIndex } from '@/routes/audit';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as categoriesIndex } from '@/routes/categories';
import { index as recurringIndex } from '@/routes/recurring';
import { index as reportsIndex } from '@/routes/reports';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as walletsIndex } from '@/routes/wallets';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const companySlug = page.props.currentCompany?.slug;
    const dashboardUrl = companySlug ? dashboard(companySlug) : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(companySlug
            ? [
                  {
                      title: 'Transactions',
                      href: transactionsIndex({ current_company: companySlug }),
                      icon: ArrowLeftRight,
                  },
                  {
                      title: 'Wallets',
                      href: walletsIndex({ current_company: companySlug }),
                      icon: WalletCards,
                  },
                  {
                      title: 'Budgets',
                      href: budgetsIndex({ current_company: companySlug }),
                      icon: PiggyBank,
                  },
                  {
                      title: 'Recurring',
                      href: recurringIndex({ current_company: companySlug }),
                      icon: RefreshCw,
                  },
                  {
                      title: 'Categories',
                      href: categoriesIndex({ current_company: companySlug }),
                      icon: Shapes,
                  },
                  {
                      title: 'Reports',
                      href: reportsIndex({ current_company: companySlug }),
                      icon: ChartColumn,
                  },
                  {
                      title: 'Audit Log',
                      href: auditIndex({ current_company: companySlug }),
                      icon: History,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <CompanySwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
