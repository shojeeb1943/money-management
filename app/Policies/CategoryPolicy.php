<?php

namespace App\Policies;

use App\Enums\CompanyPermission;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->belongsToCompany($category->company);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasCompanyPermission($category->company, CompanyPermission::ManageFinanceSetup);
    }

    public function archive(User $user, Category $category): bool
    {
        return $user->hasCompanyPermission($category->company, CompanyPermission::ManageFinanceSetup);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasCompanyPermission($category->company, CompanyPermission::ManageFinanceSetup);
    }
}
