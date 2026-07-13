<?php

namespace App\Policies;

use App\Enums\CompanyPermission;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->belongsToCompany($transaction->company);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->hasCompanyPermission($transaction->company, CompanyPermission::RecordTransactions);
    }

    public function void(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
