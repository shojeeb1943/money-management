<?php

namespace App\Policies;

use App\Enums\CompanyPermission;
use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->belongsToCompany($wallet->company);
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $user->hasCompanyPermission($wallet->company, CompanyPermission::ManageFinanceSetup);
    }

    public function archive(User $user, Wallet $wallet): bool
    {
        return $user->hasCompanyPermission($wallet->company, CompanyPermission::ManageFinanceSetup);
    }
}
