---
description: Build an advanced A2Z business + personal finance system with Laravel 13 and its React starter kit
---

# Problem

I run **UddoktaPay**, a payment gateway business in Bangladesh. We never kept proper accounts — money came in, we spent it, and now we can't tell where the business's money went. I also mix business and personal money, so I don't know what belongs to the business and what's mine.

I need one system that manages **A to Z of both business AND personal finance**.

# Key design decisions

**1. Teams = separate finance workspaces.** Use the starter kit's team feature: each team is an isolated set of books. "UddoktaPay" is one team (business), "Personal" is another team (my personal finance). I can switch teams from the navbar, invite members to the business team with roles (owner/accountant/viewer), and later add more businesses as new teams. All data — accounts, categories, transactions, reports — is scoped per team. Cross-team transfers (owner draw from business → personal, owner investment personal → business) must be tracked properly on both sides.

**2. Dynamic wallets/accounts.** No hardcoded Bank/bKash/Nagad. Users create their own money accounts with a name, type (bank / mobile banking / cash / card / savings), optional account number, opening balance, and icon/color. Seed sensible BD defaults (Bank, bKash, Nagad, Cash) that users can rename, add to, or archive. Every account shows a live balance and its own transaction ledger.

**3. Dynamic categories.** Fully user-manageable income and expense categories per team, with optional sub-categories (parent → child), icon/color, and archive (never hard-delete a category that has transactions). Seed defaults — business: Gateway Commission, Setup Fee, Server/Hosting, Marketing, Salaries…; personal: Food, Rent, Transport, Family, Zakat/Donation, Savings… — all editable.

# Features

- Record every transaction: income, expense, transfer between own accounts, cross-team owner draw/investment — currency BDT (৳), design for multi-currency later
- Business team: invoices/receivables from merchants, bills/payables to vendors, contact management
- Real financial reports per team: Income Statement, Balance Sheet, Cash Flow — accurate double-entry accounting under the hood, not just a transaction list; plus a combined net-worth view across my teams
- Dashboard per team: cash position per wallet, monthly income vs expense, profit trend, top expense categories, dues/receivables, budget alerts
- Budgets per category with alerts, recurring transactions (rent, salaries, subscriptions), CSV import/export, receipt attachments, audit log of who changed what
- Must be trustworthy: numbers must always reconcile — I never want "money disappeared" again

# Stack

**Laravel 13 with its official React starter kit is ALREADY INSTALLED in this repository** (Inertia + React + TypeScript + Tailwind). Do not scaffold a new project — build on what's here. Use the starter kit's team feature for workspace separation. Proper double-entry accounting core. Tests that prove the accounting math is correct (including cross-team transfers), and seeded demo data for both teams so I can see it working.

# Your job

1. **Explore the installed starter kit first**: Laravel/React versions, auth setup, whether the team feature exists and how it works (models, middleware, current-team switching, invitations). If teams are missing or limited, plan the team layer yourself following the kit's conventions.
2. Write a full implementation plan (architecture, schema, team scoping strategy, modules, build phases) that fits the existing codebase's structure and conventions — show me the plan for approval.
3. Build it phase by phase — run migrations, tests, and `composer test` to verify each phase works before moving to the next.

$ARGUMENTS