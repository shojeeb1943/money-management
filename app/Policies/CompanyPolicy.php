<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function delete(User $user, Company $company): bool
    {
        return Company::query()->count() > 1;
    }
}
