<?php

namespace App\Mcp\Concerns;

use App\Enums\CategoryKind;
use App\Enums\CompanyPermission;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;

trait InteractsWithCompany
{
    protected function company(Request $request): Company
    {
        $user = $this->authenticatedUser($request);
        $slug = $request->get('company');

        $company = is_string($slug) && $slug !== ''
            ? Company::query()->where('slug', $slug)->first()
            : ($user->currentCompany ?? $user->fallbackCompany());

        if (! $company instanceof Company || ! $user->belongsToCompany($company)) {
            throw ValidationException::withMessages([
                'company' => 'Unknown company. Pass a "company" slug you belong to, or omit it to use your current company.',
            ]);
        }

        return $company;
    }

    protected function authorizeRecord(Request $request, Company $company): void
    {
        $this->authorizePermission($request, $company, CompanyPermission::RecordTransactions, 'record transactions');
    }

    protected function authorizeSetup(Request $request, Company $company): void
    {
        $this->authorizePermission($request, $company, CompanyPermission::ManageFinanceSetup, 'manage finance setup');
    }

    protected function wallet(Company $company, string|int $identifier): Wallet
    {
        $wallet = is_numeric($identifier)
            ? $company->wallets()->whereKey((int) $identifier)->first()
            : null;
        $wallet ??= $company->wallets()->where('name', (string) $identifier)->first();

        if (! $wallet instanceof Wallet) {
            throw ValidationException::withMessages([
                'wallet' => "Wallet \"{$identifier}\" not found. Use the list-wallets tool to see available wallets.",
            ]);
        }

        return $wallet;
    }

    protected function category(Company $company, string|int $identifier, ?CategoryKind $kind = null): Category
    {
        $query = $company->categories()->when($kind !== null, fn ($inner) => $inner->where('kind', $kind));

        $category = is_numeric($identifier)
            ? (clone $query)->whereKey((int) $identifier)->first()
            : null;
        $category ??= (clone $query)->where('name', (string) $identifier)->first();

        if (! $category instanceof Category) {
            $kindLabel = $kind->value ?? 'any';

            throw ValidationException::withMessages([
                'category' => "Category \"{$identifier}\" (kind: {$kindLabel}) not found. Use the list-categories tool to see available categories.",
            ]);
        }

        return $category;
    }

    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages(['auth' => 'Unauthenticated.']);
        }

        return $user;
    }

    private function authorizePermission(Request $request, Company $company, CompanyPermission $permission, string $label): void
    {
        $user = $this->authenticatedUser($request);

        if (! $user->hasCompanyPermission($company, $permission)) {
            throw ValidationException::withMessages([
                'permission' => "You do not have permission to {$label} in company \"{$company->name}\".",
            ]);
        }
    }
}
