<?php

namespace App\Concerns;

use App\Data\CompanyPermissions;
use App\Data\UserCompany;
use App\Enums\CompanyPermission;
use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

trait HasCompanies
{
    /**
     * Get all of the companies the user belongs to.
     *
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_members', 'user_id', 'company_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all of the companies the user owns.
     *
     * @return HasManyThrough<Company, Membership, $this>
     */
    public function ownedCompanies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Company::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'company_id',
        )->where('company_members.role', CompanyRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function companyMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current company.
     *
     * @return BelongsTo<Company, $this>
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Get the user's personal company.
     */
    public function personalCompany(): ?Company
    {
        return $this->companies()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Switch to the given company.
     */
    public function switchCompany(Company $company): bool
    {
        if (! $this->belongsToCompany($company)) {
            return false;
        }

        $this->update(['current_company_id' => $company->id]);
        $this->setRelation('currentCompany', $company);

        URL::defaults(['current_company' => $company->slug]);

        return true;
    }

    /**
     * Determine if the user belongs to the given company.
     */
    public function belongsToCompany(Company $company): bool
    {
        return $this->companies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Determine if the given company is the user's current company.
     */
    public function isCurrentCompany(Company $company): bool
    {
        return $this->current_company_id === $company->id;
    }

    /**
     * Determine if the user is the owner of the given company.
     */
    public function ownsCompany(Company $company): bool
    {
        return $this->companyRole($company) === CompanyRole::Owner;
    }

    /**
     * Get the user's role on the given company.
     */
    public function companyRole(Company $company): ?CompanyRole
    {
        return $this->companyMemberships()
            ->where('company_id', $company->id)
            ->first()
            ?->role;
    }

    /**
     * Get the user's companies as a collection of UserCompany objects.
     *
     * @return Collection<int, UserCompany>
     */
    public function toUserCompanies(bool $includeCurrent = false): Collection
    {
        return $this->companies()
            ->get()
            ->map(fn (Company $company) => ! $includeCurrent && $this->isCurrentCompany($company) ? null : $this->toUserCompany($company))
            ->filter()
            ->values();
    }

    /**
     * Get the user's company as a UserCompany object.
     */
    public function toUserCompany(Company $company): UserCompany
    {
        $role = $this->companyRole($company);

        return new UserCompany(
            id: $company->id,
            name: $company->name,
            slug: $company->slug,
            isPersonal: $company->is_personal,
            role: $role?->value,
            roleLabel: $role?->label(),
            isCurrent: $this->isCurrentCompany($company),
            timezone: $company->timezone,
            currency: $company->currency,
        );
    }

    /**
     * Get the standard permissions for a company as a CompanyPermissions object.
     */
    public function toCompanyPermissions(Company $company): CompanyPermissions
    {
        $role = $this->companyRole($company);

        return new CompanyPermissions(
            canUpdateCompany: $role?->hasPermission(CompanyPermission::UpdateCompany) ?? false,
            canDeleteCompany: $role?->hasPermission(CompanyPermission::DeleteCompany) ?? false,
        );
    }

    public function fallbackCompany(?Company $excluding = null): ?Company
    {
        return $this->companies()
            ->when($excluding, fn ($query) => $query->where('companies.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(companies.name)')
            ->first();
    }

    /**
     * Determine if the user has the given permission on the company.
     */
    public function hasCompanyPermission(Company $company, CompanyPermission $permission): bool
    {
        return $this->companyRole($company)?->hasPermission($permission) ?? false;
    }
}
