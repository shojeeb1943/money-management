<?php

namespace App\Actions\Companies;

use App\Actions\Categories\SetupDefaultCategories;
use App\Actions\Wallets\SetupDefaultWallets;
use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCompany
{
    public function __construct(
        private SetupDefaultWallets $setupDefaultWallets,
        private SetupDefaultCategories $setupDefaultCategories,
    ) {}

    /**
     * Create a new company and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Company
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $company = Company::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $company->memberships()->create([
                'user_id' => $user->id,
                'role' => CompanyRole::Owner,
            ]);

            $this->setupDefaultWallets->handle($company);
            $this->setupDefaultCategories->handle($company);

            $user->switchCompany($company);

            return $company;
        });
    }
}
